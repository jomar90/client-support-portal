
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Ticket Response</title>
</head>
<body>
    <h2>New Response to Your Support Ticket</h2>

    <p>Hello,</p>

    <p>
        An administrator has responded to your support ticket.
    </p>

    <p>
        <strong>Ticket #{{ $ticket->id }}</strong>
    </p>

    <p>
        <strong>Title:</strong> {{ $ticket->title }}
    </p>

    <h3>Response:</h3>

    <p>{{ $response->body }}</p>

    <p>
        <a href="{{ url('/tickets/' . $ticket->id) }}">
            View Your Ticket
        </a>
    </p>

    <p>Client Support Portal</p>
</body>
</html>
