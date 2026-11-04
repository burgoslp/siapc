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
            medicoId: '',
            estado: '',

            init() {
                Livewire.on('eventos-cargados', (data) => {
                    const eventos = Array.isArray(data) ? data[0].eventos : data.eventos;
                    this.actualizarEventos(eventos);
                });

                Livewire.on('filtros-actualizados', (data) => {
                    const filtros = Array.isArray(data) ? data[0] : data;
                    this.medicoId = filtros.medicoId;
                    this.estado = filtros.estado;
                    this.recargarEventos();
                });

                setTimeout(() => {
                    const el = document.getElementById('fullcalendar-citas');
                    if (!el) return;

                    const isMobile = window.innerWidth < 768;
                    const self = this;

                    this.calendar = new Calendar(el, {
                        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
                        locale: esLocale,
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        
                        // 👇 TOOLBAR RESPONSIVO
                        headerToolbar: isMobile ? {
                            left: 'prev,next',
                            center: 'title',
                            right: 'today'
                        } : {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                        },
                        
                        buttonText: {
                            today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista'
                        },
                        slotMinTime: '07:00:00',
                        slotMaxTime: '19:00:00',
                        allDaySlot: false,
                        nowIndicator: true,
                        editable: false,
                        selectable: true,
                        dayMaxEvents: isMobile ? 3 : 4,
                        
                        // 👇 EVENTOS MINIMALISTAS EN MÓVIL (SOLO PUNTO)
                        eventContent: function(arg) {
                            // Solo mostramos el punto si estamos en móvil Y estamos en la vista de Mes
                            if (isMobile && arg.view.type === 'dayGridMonth') {
                                return {
                                    html: `<div style="width: 10px; height: 10px; border-radius: 50%; background-color: ${arg.event.backgroundColor}; margin: 2px auto;"></div>`
                                };
                            }
                            // En desktop o en la vista de Día, mostramos el texto normal
                            return true; 
                        },

                        datesSet: function (info) {
                            const start = info.startStr.substring(0, 10);
                            const end = info.endStr.substring(0, 10);
                            self.pedirEventos(start, end);
                        },
                        eventClick: function (info) {
                            info.jsEvent.preventDefault();
                            Livewire.dispatch('editar-cita', { id: parseInt(info.event.id) });
                        },
                        
                        // 👇 LÓGICA DE CLICK EN EL DÍA
                        dateClick: function (info) {
                            const fecha = info.dateStr.substring(0, 10);
                            const hora = info.dateStr.includes('T') ? info.dateStr.split('T')[1].substring(0, 5) : '09:00';
                            
                            if (isMobile) {
                                // Verificar si hay eventos en este día
                                const eventosDelDia = self.calendar.getEvents().filter(ev => {
                                    return ev.startStr.substring(0, 10) === fecha;
                                });

                                if (eventosDelDia.length > 0) {
                                    // Si hay eventos, expandir a vista Día
                                    self.calendar.changeView('timeGridDay', info.dateStr);
                                } else {
                                    // Si no hay eventos, abrir modal de nueva cita
                                    Livewire.dispatch('nueva-cita-en', { fecha: fecha, hora: hora });
                                }
                            } else {
                                // En desktop, comportamiento normal
                                Livewire.dispatch('nueva-cita-en', { fecha: fecha, hora: hora });
                            }
                        },
                        select: function (info) {
                            const fecha = info.startStr.substring(0, 10);
                            const hora = info.startStr.includes('T') ? info.startStr.split('T')[1]?.substring(0, 5) : '09:00';
                            Livewire.dispatch('nueva-cita-en', { fecha: fecha, hora: hora });
                        },
                        eventDidMount: function (info) {
                            const props = info.event.extendedProps;
                            info.el.title = `${props.medico} — ${props.estado}${props.motivo ? ' — ' + props.motivo : ''}`;
                        },
                    });

                    this.calendar.render();

                }, 150);
            },

            pedirEventos(start, end) {
                Livewire.dispatch('cargar-eventos', { start: start, end: end, medicoId: this.medicoId, estado: this.estado });
            },
            actualizarEventos(eventos) {
                if (!this.calendar) return;
                this.calendar.removeAllEvents();
                if (eventos && eventos.length > 0) {
                    eventos.forEach(ev => this.calendar.addEvent(ev));
                }
            },
            recargarEventos() {
                if (!this.calendar) return;
                const view = this.calendar.view;
                const start = view.activeStart.toISOString().substring(0, 10);
                const end = view.activeEnd.toISOString().substring(0, 10);
                this.pedirEventos(start, end);
            },
        }));
    });
}
registrarCalendarioCitas();