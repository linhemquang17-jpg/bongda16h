# ==============================================================================
# SCRIPT TỰ ĐỘNG GIT COMMIT & PUSH (WATCHER & DEBOUNCE)
# Dự án: Website Đặt Sân Bóng Đá BongDa16h
# ==============================================================================

param (
    [int]$DebounceSeconds = 8,
    [int]$PollIntervalSeconds = 2,
    [string]$RemoteName = "origin"
)

# Cấu hình UTF-8 cho PowerShell
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# Chuyển về thư mục gốc của dự án
$ProjectRoot = (Get-Item -Path "$PSScriptRoot\..").FullName
if (-not (Test-Path "$ProjectRoot\.git")) {
    $ProjectRoot = (Get-Location).Path
}
Set-Location $ProjectRoot

Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host "  HỆ THỐNG TỰ ĐỘNG GIT COMMIT & PUSH - DỰ ÁN BONGDA16H" -ForegroundColor Cyan
Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host "Thư mục dự án : $ProjectRoot" -ForegroundColor Gray

# 1. Kiểm tra Git repository
$isGit = git rev-parse --is-inside-work-tree 2>$null
if ($isGit -ne "true") {
    Write-Host "[LỖI] Thư mục hiện tại không phải là một Git repository!" -ForegroundColor Red
    exit 1
}

# 2. Xác định branch hiện tại
$CurrentBranch = git branch --show-current
if ([string]::IsNullOrWhiteSpace($CurrentBranch)) {
    $CurrentBranch = "main"
}
Write-Host "Branch hiện tại: $CurrentBranch" -ForegroundColor Gray

# 3. Kiểm tra Remote URL
$RemoteUrl = git remote get-url $RemoteName 2>$null
if (-not $RemoteUrl) {
    Write-Host "[CẢNH BÁO] Chưa cấu hình Git Remote '$RemoteName'!" -ForegroundColor Yellow
    Write-Host "Vui lòng cấu hình bằng lệnh: git remote add origin <URL_GITHUB>" -ForegroundColor Yellow
    exit 1
}
Write-Host "Remote GitHub  : $RemoteUrl" -ForegroundColor Gray
Write-Host "Thời gian gom  : $DebounceSeconds giây sau lần Ctrl + S cuối cùng" -ForegroundColor Gray
Write-Host "Trạng thái     : ĐANG THEO DÕI THAY ĐỔI FILE (Nhấn Ctrl + C để dừng)" -ForegroundColor Green
Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host ""

# Danh sách mẫu file nhạy cảm tuyệt đối cấm đưa lên Git (Lớp bảo vệ thứ 2 sau .gitignore)
$BannedPatterns = @(
    "*backup*.sql",
    "bongda16h_backup.sql",
    "*.env*",
    "database.local.php",
    "*.zip",
    "*.rar",
    "credentials.json",
    "auth.json",
    "ftp_credentials.*"
)

function Test-IsSensitiveFile($filePath) {
    $fileName = Split-Path $filePath -Leaf
    foreach ($pattern in $BannedPatterns) {
        if ($fileName -like $pattern -or $filePath -like $pattern) {
            return $true
        }
    }
    return $false
}

while ($true) {
    try {
        # Kiểm tra trạng thái thay đổi
        $statusOutput = (git status --porcelain 2>$null | Out-String).Trim()
        
        if ($statusOutput) {
            $timestamp = (Get-Date).ToString("HH:mm:ss")
            Write-Host "[$timestamp] [PHÁT HIỆN LƯU FILE] Đang chờ $DebounceSeconds giây gom các thay đổi tiếp theo..." -ForegroundColor Yellow
            
            # Cơ chế Debounce: Chờ thêm thời gian để gom các file cùng được sửa
            $lastText = $statusOutput
            $waited = 0
            $maxWait = 15
            while ($waited -lt $DebounceSeconds) {
                Start-Sleep -Seconds 1
                $waited++
                $currentText = (git status --porcelain 2>$null | Out-String).Trim()
                if ($currentText -ne $lastText) {
                    $lastText = $currentText
                    Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Phát hiện thêm file được lưu, gia hạn chờ..." -ForegroundColor DarkYellow
                    if ($waited + 3 -le $maxWait) {
                        $waited = [Math]::Max(0, $waited - 2)
                    }
                }
            }

            # Chạy git add
            git add -A

            # Lấy danh sách các file được staged
            $stagedRaw = git diff --cached --name-only 2>$null
            $stagedFiles = @($stagedRaw | Where-Object { -not [string]::IsNullOrWhiteSpace($_) })

            if ($stagedFiles.Count -eq 0) {
                Start-Sleep -Seconds $PollIntervalSeconds
                continue
            }

            # Lớp bảo vệ: Kiểm tra xem có file nhạy cảm nào lọt vào không
            foreach ($file in $stagedFiles) {
                if (Test-IsSensitiveFile $file) {
                    Write-Host "[BẢO MẬT] CẢNH BÁO: Phát hiện file nhạy cảm '$file' trong staging! HỦY KHỎI COMMIT." -ForegroundColor Red
                    git reset HEAD $file 2>$null
                }
            }

            # Nếu sau khi loại trừ không còn file nào
            $stagedRawAfter = git diff --cached --name-only 2>$null
            $stagedFilesAfter = @($stagedRawAfter | Where-Object { -not [string]::IsNullOrWhiteSpace($_) })
            if ($stagedFilesAfter.Count -eq 0) {
                Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Không có file hợp lệ nào để commit." -ForegroundColor DarkGray
                Start-Sleep -Seconds $PollIntervalSeconds
                continue
            }

            # Rút gọn danh sách file cho nội dung commit
            $fileListSummary = @()
            $count = 0
            foreach ($f in $stagedFilesAfter) {
                $count++
                if ($count -le 3) {
                    $fileListSummary += (Split-Path $f -Leaf)
                }
            }
            if ($count -gt 3) {
                $fileListSummary += "va $($count - 3) file khac"
            }
            $fileSummaryText = $fileListSummary -join ", "

            $nowStr = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
            $commitMessage = "Auto: Cap nhat $fileSummaryText [$nowStr]"

            # Thực hiện commit
            Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Đang tạo commit: '$commitMessage'..." -ForegroundColor Cyan
            $commitRes = git commit -m "$commitMessage" 2>&1

            if ($LASTEXITCODE -ne 0) {
                Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Commit không thành công hoặc không có thay đổi mới." -ForegroundColor Yellow
                Start-Sleep -Seconds $PollIntervalSeconds
                continue
            }

            # Thực hiện git push
            Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Đang đẩy (push) lên GitHub ($RemoteName/$CurrentBranch)..." -ForegroundColor Cyan
            $pushOutput = git push $RemoteName $CurrentBranch 2>&1
            $pushSuccess = ($LASTEXITCODE -eq 0)

            if ($pushSuccess) {
                Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] [THÀNH CÔNG] Đã push lên GitHub thành công!" -ForegroundColor Green
                Write-Host "--------------------------------------------------------------" -ForegroundColor DarkGray
            } else {
                Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] [LỖI PUSH LÊN GITHUB]" -ForegroundColor Red
                $pushErrorStr = $pushOutput | Out-String
                Write-Host $pushErrorStr -ForegroundColor DarkRed

                if ($pushErrorStr -match "fetch first" -or $pushErrorStr -match "rejected") {
                    Write-Host "[HƯỚNG DẪN XỬ LÝ]: Repository trên GitHub có commit mới hơn ở máy của bạn." -ForegroundColor Yellow
                    Write-Host "Để bảo toàn dữ liệu, hệ thống KHÔNG tự ý ghi đè (không dùng --force)." -ForegroundColor Yellow
                    Write-Host "Vui lòng mở Terminal và chạy lệnh: git pull --rebase origin $CurrentBranch" -ForegroundColor Yellow
                } elseif ($pushErrorStr -match "Could not resolve host" -or $pushErrorStr -match "Failed to connect") {
                    Write-Host "[HƯỚNG DẪN XỬ LÝ]: Mất kết nối Internet hoặc không kết nối được GitHub." -ForegroundColor Yellow
                    Write-Host "Code của bạn đã được commit an toàn tại local và sẽ tự động được push khi có mạng." -ForegroundColor Yellow
                } elseif ($pushErrorStr -match "Permission to" -or $pushErrorStr -match "Authentication failed") {
                    Write-Host "[HƯỚNG DẪN XỬ LÝ]: Lỗi xác thực tài khoản GitHub." -ForegroundColor Yellow
                    Write-Host "Vui lòng đăng nhập lại qua Git Credential Manager hoặc kiểm tra quyền truy cập repository." -ForegroundColor Yellow
                }
                Write-Host "--------------------------------------------------------------" -ForegroundColor DarkGray
            }
        }
    } catch {
        Write-Host "[LỖI HỆ THỐNG]: $_" -ForegroundColor Red
    }

    Start-Sleep -Seconds $PollIntervalSeconds
}

