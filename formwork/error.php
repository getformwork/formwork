<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= isset($message) ? $message : 'Internal Server Error' ?> | Formwork</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    @media (min-width: 768px) {
        html {
            font-size: 18px;
        }
    }

    body {
        background-color: #f8f8f8;
        color: #666;
        font-family: sans-serif;
    }

    .container {
        margin: 2rem auto;
        padding: 1rem;
        max-width: 32rem;
        text-align: center;
    }

    h1 {
        margin-bottom: 3rem;
        font-size: 1.75rem;
    }

    h2 {
        margin-bottom: 1rem;
        font-size: 2rem;
    }

    a {
        outline: none;
        color: #3498da;
        text-decoration: none;
        transition: color 150ms;
    }

    a:hover {
        color: #1a608e;
    }

    p {
        line-height: 1.5;
    }

    .error-code {
        display: block;
        color: #999;
        font-weight: 400;
        font-size: 8rem;
    }

    .error-status {
        display: block;
        color: #999;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="error-code"><?= isset($status) ? $status : 500 ?></span>
            <span class="error-status"><?= isset($message) ? $message : 'Internal Server Error'  ?></span>
        </h1>
        <h2>Oops, something went wrong!</h2>
        <p>Formwork encountered an error while serving your request. Please check Formwork configuration or the server log for errors.</p>
        <p><a href="https://github.com/getformwork/formwork/issues" target="_blank">Report an issue to GitHub</a></p>
    </div>
</body>
</html>
