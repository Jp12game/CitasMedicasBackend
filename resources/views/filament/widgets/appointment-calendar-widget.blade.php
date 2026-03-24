<x-filament-widgets::widget>
    <x-filament::section heading="Calendario de Citas">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">

        <div wire:ignore id="fc-container" style="min-height: 580px;"></div>

    </x-filament::section>
</x-filament-widgets::widget>

@script
<script>
    const events = @json(json_decode($events));

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
    script.onload = function () {
        const el = document.getElementById('fc-container');
        const calendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            events: events,
            height: 'auto',
        });
        calendar.render();
    };
    document.head.appendChild(script);
</script>
@endscript
