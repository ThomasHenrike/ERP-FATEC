{{--
    Logo MERP embutido como SVG inline (identidade visual v1.0, arquivos em public/brand/merp/).

    Inline, e nao <img src="...svg">, porque os SVGs com texto usam a fonte Inter: dentro de
    um <img> o navegador nao aplica as fontes carregadas pela pagina e o "MERP" cairia em
    Arial. Inline, o texto herda a Inter que o layout ja carrega.

    Variantes: horizontal (fundo escuro), horizontal-claro, vertical, vertical-claro,
    simbolo, simbolo-claro. Tamanho vem da classe (ex: class="h-10 w-auto").
--}}
@props(['variante' => 'horizontal'])

@php
    $permitidas = ['horizontal', 'horizontal-claro', 'vertical', 'vertical-claro', 'simbolo', 'simbolo-claro'];
    $variante = in_array($variante, $permitidas, true) ? $variante : 'horizontal';

    $svg = file_get_contents(public_path("brand/merp/merp-{$variante}.svg"));
    // Remove largura/altura fixas do arquivo para o tamanho vir so da classe CSS.
    $svg = preg_replace('/\s(width|height)="[^"]*"/', '', $svg, 2);
    $svg = preg_replace(
        '/^<svg /',
        '<svg role="img" aria-label="MERP" class="' . e($attributes->get('class', 'h-10 w-auto')) . '" ',
        $svg
    );
@endphp

{!! $svg !!}
