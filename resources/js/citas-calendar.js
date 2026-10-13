import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import esLocale from '@fullcalendar/core/locales/es';

export function registrarCalendarioCitas() {
    document.addEventListener('alpine:init', () => {
        Alpine.data('calendarioCitas', () => ({
            calendar: null,

            init() {
                const self = this;

                this.calendar = new Calendar(
                    document.getElementById('fullcalendar-citas'),
                    {
                        plugins: [
                            dayGridPlugin,
                            timeGridPlugin,
                            interactionPlugin,
                            listPlugin,
                        ],
                        locale: esLocale,
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                        },
                        buttonText: {
                            today: 'Hoy',
                            month: 'Mes',
                            week: 'Semana',
                            day: 'Día',
                            list: 'Lista'
                        },
                        slotMinTime: '07:00:00',
                        slotMaxTime: '19:00:00',
                        allDaySlot: false,
                        nowIndicator: true,
                        editable: false,
                        selectable: true,
                        dayMaxEvents: 3,

                        datesSet: function (info) {
                            self.pedirEventos(info.startStr, info.endStr);
                        },
                        eventClick: function (info) {
                            info.jsEvent.preventDefault();
                            Livewire.dispatch('editar-cita', { id: parseInt(info.event.id) });
                        },
                        dateClick: function (info) {
                            const fecha = info.dateStr.split('T')[0];
                            const hora = info.dateStr.includes('T')
                                ? info.dateStr.split('T')[1].substring(0, 5)
                                : '09:00';
                            Livewire.dispatch('nueva-cita-en', { fecha: fecha, hora: hora });
                        },
                        select: function (info) {
                            const fecha = info.startStr.split('T')[0];
                            const hora = info.startStr.split('T')[1]?.substring(0, 5) ?? '09:00';
                            Livewire.dispatch('nueva-cita-en', { fecha: fecha, hora: hora });
                        },
                        eventDidMount: function (info) {
                            const props = info.event.extendedProps;
                            info.el.title = `${props.medico} — ${props.estado}${props.motivo ? ' — ' + props.motivo : ''}`;
                        },
                    }
                );

                this.calendar.render();
            },

            pedirEventos(start, end) {
                Livewire.dispatch('cargar-eventos', { start: start, end: end });
            },
            actualizarEventos(eventos) {
                if (!this.calendar) return;
                this.calendar.removeAllEvents();
                eventos.forEach(ev => this.calendar.addEvent(ev));
            },
            recargarEventos() {
                if (!this.calendar) return;
                const view = this.calendar.view;
                this.pedirEventos(view.activeStart.toISOString(), view.activeEnd.toISOString());
            },
        }));
    });
    
}
registrarCalendarioCitas();