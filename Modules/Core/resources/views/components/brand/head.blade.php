{{--
    Base visual MERP compartilhada pelos layouts (Core master e Admin Supremo):
    favicons, fonte Inter e o tailwind.config com a paleta oficial.

    Estrategia de menor risco (sem reescrever telas): as escalas que as telas ja usam
    sao remapeadas aqui para a paleta MERP.
    - indigo (era a primaria) -> verde MERP, deslocado um tom para manter contraste:
      bg-indigo-600 com texto branco vira verde 700 (#237552, 5,6:1). O verde 600
      (#2F9A6C) com texto branco fica em 3,5:1, abaixo do minimo para texto pequeno.
    - gray -> cinzas MERP pela luminosidade (nao pelo numero): a escala MERP e mais clara
      nos tons medios, e mapear 500->500 derrubaria o texto secundario para 2,9:1.
    - white/black -> extremos da paleta (o guia nao usa branco nem preto puros).
    - pesos acima de 500 viram 500 (o guia para no Medium; a Inter e carregada so em 400/500).
--}}
<x-core::brand.favicons />

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
                fontWeight: {
                    semibold: '500',
                    bold: '500',
                    extrabold: '500',
                    black: '500',
                },
                colors: {
                    white: '#FBFCFF',
                    black: '#161826',
                    indigo: {
                        50: '#EAFAF2', 100: '#C9F2E0', 200: '#9FE6C7', 300: '#6FD4A9', 400: '#3FBF87',
                        500: '#2F9A6C', 600: '#237552', 700: '#19543B', 800: '#113526', 900: '#113526', 950: '#113526',
                    },
                    gray: {
                        50: '#F7F8FE', 100: '#F3F5FE', 200: '#E4E7F5', 300: '#CFD3E5', 400: '#9397AB',
                        500: '#595D6C', 600: '#3F424D', 700: '#3F424D', 800: '#292B31', 900: '#161826', 950: '#161826',
                    },
                    merp: {
                        grafite: '#161826',
                        superficie: '#232532',
                        nevoa: '#E4E7F5',
                        verde: {
                            DEFAULT: '#3FBF87',
                            100: '#EAFAF2', 200: '#C9F2E0', 300: '#9FE6C7', 400: '#6FD4A9', 500: '#3FBF87',
                            600: '#2F9A6C', 700: '#237552', 800: '#19543B', 900: '#113526',
                        },
                    },
                },
            },
        },
    }
</script>
<style>
    [x-cloak] { display: none !important; }
    /* <b>/<strong>/<th> usam "bolder" por padrao: com a Inter so em 400/500 o navegador
       engordaria a letra artificialmente. */
    b, strong, th { font-weight: 500; }
</style>
