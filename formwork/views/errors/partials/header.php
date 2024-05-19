<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $message ?? 'Internal Server Error' ?> | Formwork</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            background-color: #f7f7f7;
            color: #262626;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .container {
            max-width: 32rem;
            padding: 1rem;
            margin: 4rem auto;
            text-align: center;
        }

        h1,
        h2,
        h3 {
            margin-top: 0;
            letter-spacing: -0.027rem;
            line-height: 1.2;
        }

        h1 {
            margin-bottom: 3rem;
            font-size: 1.75rem;
            font-weight: 500;
        }

        h2 {
            margin-bottom: 1rem;
            font-size: 2rem;
            font-weight: 500;
        }

        h3 {
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
            font-weight: 500;
        }

        a {
            color: #3498da;
            outline: none;
            text-decoration: none;
            transition: color 150ms;
        }

        a:hover {
            color: #1a608e;
        }

        p {
            line-height: 1.5;
        }

        strong {
            font-weight: 600;
        }

        code {
            color: #7d7d7d;
            font-family: SFMono-Regular, 'SF Mono', 'Cascadia Mono', 'Liberation Mono', Menlo, Consolas, monospace;
            font-size: 0.875em;
        }

        .error-code {
            display: block;
            color: #969696;
            font-size: 8rem;
            font-weight: 400;
        }

        .error-status {
            display: block;
            color: #969696;
        }

        .error-debug-details .__formwork-code {
            margin-right: 0;
            margin-left: 0;
        }

        .error-debug-details {
            margin: 0 auto 4rem;
            max-width: 87.5rem;
            text-align: left;
            border-radius: 4px;
            background-color: #fff;
            padding: 1.5rem 2rem;
        }

        .error-debug-editor-uri {
            color: inherit;
            text-decoration: underline;
            text-decoration-color: #cacaca;
            text-decoration-style: dashed;
        }

        .error-debug-filename,
        .error-debug-line {
            font-size: 0.875em;
        }

        .error-debug-filename {
            word-break: break-all;
        }

        .error-debug-line {
            color: #7d7d7d;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>
            <span class="error-code"><?= $status ?? 500 ?></span>
            <span class="error-status"><?= $message ?? 'Internal Server Error' ?></span>
        </h1>