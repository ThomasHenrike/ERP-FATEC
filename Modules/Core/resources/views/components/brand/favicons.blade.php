{{-- Favicons e icone de app MERP (identidade visual v1.0). Usado no <head> de todas as paginas,
     inclusive as de impressao (cupom, romaneio, orcamento), que nao passam pelo layout principal.
     O apple-touch-icon usa o icone de app (fundo verde 900) e nao o favicon transparente,
     porque o iOS preenche transparencia com preto. --}}
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/merp/merp-favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('brand/merp/merp-favicon-64.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('brand/merp/merp-icone-app-512.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/merp/merp-apple-touch-icon-180.png') }}">
<meta name="theme-color" content="#161826">
