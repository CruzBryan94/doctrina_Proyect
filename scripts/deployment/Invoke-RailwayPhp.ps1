param(
    [Parameter(Mandatory = $true)][string]$PhpFile
)
$ErrorActionPreference = 'Stop'
$railwayExecutable = Join-Path $env:APPDATA 'npm\node_modules\@railway\cli\bin\railway.exe'
if (!(Test-Path -LiteralPath $railwayExecutable)) { throw 'Railway executable not available.' }
$inputScript = [System.IO.File]::ReadAllText((Resolve-Path -LiteralPath $PhpFile))
$processInfo = [System.Diagnostics.ProcessStartInfo]::new()
$processInfo.FileName = $railwayExecutable
$processInfo.UseShellExecute = $false
$processInfo.RedirectStandardInput = $true
$processInfo.RedirectStandardOutput = $true
$processInfo.RedirectStandardError = $true
$processInfo.StandardInputEncoding = [System.Text.UTF8Encoding]::new($false)
foreach ($argument in @('ssh','--project','b437a3bd-4765-4ec2-aca4-4e6f7346390c','--environment','46213611-5c6e-4cd7-b028-154a7c464901','--service','8366b39a-c537-41bf-acf1-20cca0bdc3a4','--','php')) {
    $processInfo.ArgumentList.Add($argument)
}
$remoteProcess = [System.Diagnostics.Process]::new()
$remoteProcess.StartInfo = $processInfo
[void]$remoteProcess.Start()
$stdoutTask = $remoteProcess.StandardOutput.ReadToEndAsync()
$stderrTask = $remoteProcess.StandardError.ReadToEndAsync()
$remoteProcess.StandardInput.Write($inputScript)
$remoteProcess.StandardInput.Close()
if (!$remoteProcess.WaitForExit(120000)) { throw 'Remote operation still pending; inspect target state before any retry.' }
Write-Output $stdoutTask.GetAwaiter().GetResult()
Write-Output $stderrTask.GetAwaiter().GetResult()
if ($remoteProcess.ExitCode -ne 0) { throw ('Remote PHP failed with exit ' + $remoteProcess.ExitCode) }
