{{-- Tags para o sistema tratar a página como aplicativo quando o operador
     usa "Adicionar à tela de início". Incluído nos três layouts. --}}

<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#ffffff">

{{-- Android --}}
<meta name="mobile-web-app-capable" content="yes">

{{-- iOS não lê o manifest: precisa destas, e do ícone em PNG --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Carregamento">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
