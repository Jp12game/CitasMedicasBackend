<x-filament-widgets::widget>
    <x-filament::section heading="Calendario de Citas">

        {{-- FullCalendar CSS --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">

        <div id="appointment-calendar" style="min-height: 600px;"></div>

        {{-- FullCalendar JS --}}
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('appointment-calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                    },
                    events: {!! $events !!},
                    eventClick: function (info) {
                        alert('Cita: ' + info.event.title);
                    },
                    height: 'auto',
                });
                calendar.render();
            });
        </script>

    </x-filament::section>
</x-filament-widgets::widget>
