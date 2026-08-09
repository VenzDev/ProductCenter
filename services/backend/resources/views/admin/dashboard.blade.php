<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin — {{ config('app.name') }}</title>
</head>
<body>
    <p>Signed in as {{ $admin->name }} ({{ $admin->email }}).</p>

    <form method="POST" action="/admin/logout">
        @csrf
        <button type="submit">Log out</button>
    </form>
</body>
</html>
