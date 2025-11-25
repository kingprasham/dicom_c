Set WshShell = CreateObject("WScript.Shell")

' Run auto sync in hidden window
WshShell.Run "cmd /c ""cd /d c:\xampp\htdocs\papa\dicom_again && AUTO_SYNC_LOCAL.bat""", 0, False

Set WshShell = Nothing
