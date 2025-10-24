<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please Wait...</title>
    <script src="./tailwind.js"></script>
    <style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .spin {
        animation: spin 1s linear infinite;
    }
    </style>
</head>

<body class="flex items-center justify-center mt-12 bg-gray-100">
    <div class="flex flex-col items-center">
        <div class="loader border-t-4 border-blue-500 rounded-full w-16 h-16 spin"></div>
        <p class="mt-4 text-lg text-gray-700">Do not close the app</p>
    </div>
    <script>
    location.replace('YOUR_ENDPOINT')
    </script>
</body>

</html>