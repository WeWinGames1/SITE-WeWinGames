<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Support Ticket</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="color: #0d6efd; margin-top: 0;">New Support Ticket</h2>
        <p style="font-size: 16px; margin-bottom: 0;">A new support ticket has been submitted.</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;">
        <h3 style="color: #495057; border-bottom: 2px solid #0d6efd; padding-bottom: 10px;">Ticket Information</h3>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px 0; font-weight: bold; width: 150px;">Ticket Number:</td>
                <td style="padding: 10px 0;"><strong style="color: #0d6efd;">{{ $ticket->ticket_number }}</strong></td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="padding: 10px 0; font-weight: bold;">Subject:</td>
                <td style="padding: 10px 0;">{{ $ticket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight: bold;">Category:</td>
                <td style="padding: 10px 0;">{{ $ticket->category->name ?? 'N/A' }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="padding: 10px 0; font-weight: bold;">Priority:</td>
                <td style="padding: 10px 0;">
                    <span style="display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; text-transform: uppercase;
                        @if($ticket->priority === 'urgent') background-color: #dc3545; color: white;
                        @elseif($ticket->priority === 'high') background-color: #fd7e14; color: white;
                        @elseif($ticket->priority === 'medium') background-color: #ffc107; color: #000;
                        @else background-color: #6c757d; color: white;
                        @endif
                    ">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight: bold;">Submitted By:</td>
                <td style="padding: 10px 0;">
                    @if($ticket->is_guest_submission)
                        {{ $ticket->guest_name }} (Guest)<br>
                        <a href="mailto:{{ $ticket->guest_email }}" style="color: #0d6efd;">{{ $ticket->guest_email }}</a>
                    @else
                        {{ $ticket->user->name ?? 'N/A' }}<br>
                        <a href="mailto:{{ $ticket->user->email ?? '' }}" style="color: #0d6efd;">{{ $ticket->user->email ?? '' }}</a>
                    @endif
                </td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="padding: 10px 0; font-weight: bold;">Created:</td>
                <td style="padding: 10px 0;">{{ $ticket->created_at->format('M d, Y g:i A') }}</td>
            </tr>
        </table>

        <h4 style="color: #495057; margin-top: 20px; margin-bottom: 10px;">Message:</h4>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0d6efd; border-radius: 3px;">
            <p style="margin: 0; white-space: pre-wrap;">{{ $ticket->content }}</p>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="margin: 0;"><a href="{{ config('app.url') }}/admin/support-tickets/{{ $ticket->id }}" style="display: inline-block; background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">View & Respond in Admin Dashboard</a></p>
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
        <p style="margin: 0;">This email was sent from {{ config('app.name') }}</p>
    </div>
</body>
</html>
