<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title . ' | ' . config('app.name', 'Meltiq CIS') : config('app.name', 'Meltiq CIS') }}
</title>

<link rel="icon" href="{{ asset('assets/logo/light.jpeg') }}" sizes="any">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
