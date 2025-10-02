<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Resume Submission</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="color: #0d6efd; margin-top: 0;">New Resume Submission</h2>
        <p style="font-size: 16px; margin-bottom: 0;">A new job application has been received.</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;">
        <h3 style="color: #495057; border-bottom: 2px solid #0d6efd; padding-bottom: 10px;">Applicant Information</h3>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px 0; font-weight: bold; width: 150px;">Name:</td>
                <td style="padding: 10px 0;">{{ $submission->full_name }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="padding: 10px 0; font-weight: bold;">Email:</td>
                <td style="padding: 10px 0;"><a href="mailto:{{ $submission->email }}" style="color: #0d6efd;">{{ $submission->email }}</a></td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight: bold;">Phone:</td>
                <td style="padding: 10px 0;">{{ $submission->phone }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="padding: 10px 0; font-weight: bold;">Position:</td>
                <td style="padding: 10px 0;">{{ $submission->position }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight: bold;">Submitted:</td>
                <td style="padding: 10px 0;">{{ $submission->created_at->format('M d, Y g:i A') }}</td>
            </tr>
        </table>

        <h4 style="color: #495057; margin-top: 20px; margin-bottom: 10px;">About / Cover Letter:</h4>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0d6efd; border-radius: 3px;">
            <p style="margin: 0; white-space: pre-wrap;">{{ $submission->about }}</p>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="margin: 0;"><a href="{{ config('app.url') }}/admin/resume-submissions" style="display: inline-block; background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">View in Admin Dashboard</a></p>
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
        <p style="margin: 0;">This email was sent from {{ config('app.name') }}</p>
        <p style="margin: 5px 0 0 0;">IP Address: {{ $submission->ip_address }}</p>
    </div>
</body>
</html>
