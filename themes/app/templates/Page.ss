<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XRB Spaniel Generator 5000</title>
    <% if $ViteDevMode %>
        <script type="module" src="http://localhost:3000/@vite/client"></script>
    <% else %>
        <% loop $ViteCSSFiles %>
            <link rel="stylesheet" href="$URL">
        <% end_loop %>
    <% end_if %>
</head>
<body>
    <div id="app"></div>
    <% if $ViteDevMode %>
        <script type="module" src="http://localhost:3000/themes/app/src/js/main.js"></script>
    <% else %>
        <script type="module" src="$ViteScriptURL"></script>
    <% end_if %>
</body>
</html>
