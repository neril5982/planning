<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
  <title><?= htmlspecialchars($page_title ?? 'Planning') ?> — Portail</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            accent: { DEFAULT: '#ADBF5E', dark: '#8CA34D', light: '#EEF2DE' },
            secondary: { DEFAULT: '#53727F' },
            danger: { DEFAULT: '#E24B4A', dark: '#CC3C3B' },
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="page-bg text-gray-900 antialiased">
