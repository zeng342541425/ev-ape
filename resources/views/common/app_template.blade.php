<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $template_title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        img {
            max-width: 100%;
            height: auto;
        }
        iframe {
            max-width: 100%;
        }
    </style>
</head>
<body>
<div class="container">
    {!! $template_content !!}
</div>
</body>
</html>
