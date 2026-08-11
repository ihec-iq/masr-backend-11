<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Notification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }

        .header.success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .header.failure {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .content {
            padding: 30px 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .status-badge.success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.failure {
            background-color: #f8d7da;
            color: #721c24;
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
            text-align: right;
        }

        .message-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .message-box.success {
            background-color: #d4edda;
            border-left-color: #28a745;
        }

        .message-box.failure {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }

        .files-section {
            margin: 30px 0;
        }

        .files-section h3 {
            font-size: 18px;
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .file-item {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .file-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
            word-break: break-all;
        }

        .download-button {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
            transition: transform 0.2s;
        }

        .download-button:hover {
            transform: translateY(-2px);
        }

        .footer {
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            margin: 5px 0;
        }

        .checksum {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #6c757d;
            word-break: break-all;
            margin-top: 5px;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .email-container {
                border-radius: 0;
            }

            .header h1 {
                font-size: 24px;
            }

            .info-row {
                flex-direction: column;
            }

            .info-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header {{ $isSuccess ? 'success' : 'failure' }}">
            <div class="icon">
                @if($isSuccess)
                    ✓
                @else
                    ✗
                @endif
            </div>
            <h1>Backup {{ ucfirst($event) }}</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Status Badge -->
            <div style="text-align: center;">
                <span class="status-badge {{ $isSuccess ? 'success' : 'failure' }}">
                    {{ strtoupper($event) }}
                </span>
            </div>

            <!-- Message Box -->
            <div class="message-box {{ $isSuccess ? 'success' : 'failure' }}">
                <strong>{{ $isSuccess ? 'Success!' : 'Failed!' }}</strong><br>
                {{ $payload['message'] ?? 'No message provided' }}
            </div>

            <!-- Backup Information -->
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Event Type:</span>
                    <span class="info-value">{{ $payload['event'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Timestamp:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($payload['timestamp'])->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Backup Type:</span>
                    <span class="info-value">
                        @if($payload['type'] === 'db+files')
                            <strong>Database + Files</strong>
                        @elseif($payload['type'] === 'db')
                            <strong>Database Only</strong>
                        @else
                            <strong>Files Only</strong>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Size:</span>
                    <span class="info-value">
                        @php
                            $size = $payload['size'] ?? 0;
                            if ($size > 1024 * 1024 * 1024) {
                                echo number_format($size / (1024 * 1024 * 1024), 2) . ' GB';
                            } elseif ($size > 1024 * 1024) {
                                echo number_format($size / (1024 * 1024), 2) . ' MB';
                            } elseif ($size > 1024) {
                                echo number_format($size / 1024, 2) . ' KB';
                            } else {
                                echo number_format($size) . ' bytes';
                            }
                        @endphp
                    </span>
                </div>
                @if(!empty($payload['paths']))
                <div class="info-row">
                    <span class="info-label">Files Count:</span>
                    <span class="info-value">{{ count($payload['paths']) }} file(s)</span>
                </div>
                @endif
            </div>

            <!-- Backup Files -->
            @if(!empty($payload['temp_urls']))
            <div class="files-section">
                <h3>📦 Backup Files</h3>
                @foreach($payload['temp_urls'] as $path => $url)
                <div class="file-item">
                    <div class="file-name">📄 {{ basename($path) }}</div>
                    @if(isset($payload['checksums'][$path]))
                    <div class="checksum">
                        <strong>Checksum:</strong> {{ $payload['checksums'][$path] }}
                    </div>
                    @endif
                    <a href="{{ $url }}" class="download-button">⬇ Download Backup</a>
                </div>
                @endforeach
            </div>
            @elseif(!empty($payload['paths']))
            <div class="files-section">
                <h3>📦 Backup Files</h3>
                @foreach($payload['paths'] as $path)
                <div class="file-item">
                    <div class="file-name">📄 {{ basename($path) }}</div>
                    @if(isset($payload['checksums'][$path]))
                    <div class="checksum">
                        <strong>Checksum:</strong> {{ $payload['checksums'][$path] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ config('app.name', 'Laravel') }}</strong></p>
            <p>Automated Backup System</p>
            <p style="font-size: 12px; margin-top: 10px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
