@php
    $focus = request('focus');
    $dashboardPayment = is_array($dashboardPayment ?? null) ? $dashboardPayment : [];
    $dayNames = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];
    $money = static fn (int $amount): string => '$'.number_format($amount / 100, 2);
@endphp

<x-layouts::app :title="__('Panel de control')">
    <style>
        .clinic-shell {
            --paper: #f7f1e3;
            --ink: #1f1b18;
            --muted: #6f665d;
            --accent: #c9652b;
            --accent-soft: #f2d2bb;
            --panel: rgba(255, 252, 245, 0.88);
            --line: rgba(38, 29, 20, 0.12);
            background:
                radial-gradient(circle at top left, rgba(201, 101, 43, 0.22), transparent 28rem),
                radial-gradient(circle at top right, rgba(89, 137, 109, 0.18), transparent 26rem),
                linear-gradient(180deg, #f3ebdb 0%, #f9f4e8 46%, #efe4cf 100%);
            border: 1px solid rgba(51, 39, 29, 0.08);
            border-radius: 2rem;
            padding: 1.5rem;
            color: var(--ink);
            box-shadow: 0 20px 60px rgba(63, 41, 18, 0.08);
            overflow: hidden;
        }

        .clinic-hero {
            position: relative;
            display: grid;
            gap: 1.25rem;
            grid-template-columns: minmax(0, 1.7fr) minmax(20rem, 1fr);
            padding: 1.5rem;
            border-radius: 1.75rem;
            background:
                linear-gradient(135deg, rgba(36, 29, 25, 0.95), rgba(94, 54, 29, 0.9)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.2), transparent);
            color: #fff6ea;
            overflow: hidden;
        }

        .clinic-hero::after {
            content: '';
            position: absolute;
            inset: auto -2rem -3rem auto;
            width: 14rem;
            height: 14rem;
            border-radius: 999px;
            background: rgba(255, 243, 220, 0.08);
            box-shadow: 0 0 0 2rem rgba(255, 243, 220, 0.04);
        }

        .clinic-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 244, 229, 0.12);
            border: 1px solid rgba(255, 244, 229, 0.18);
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .clinic-title {
            margin-top: 1rem;
            font-family: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
            font-size: clamp(2.3rem, 5vw, 4rem);
            line-height: 0.94;
            letter-spacing: -0.05em;
            max-width: 11ch;
        }

        .clinic-copy {
            max-width: 56ch;
            color: rgba(255, 246, 234, 0.82);
            font-size: 1rem;
            line-height: 1.6;
            margin-top: 1rem;
        }

        .clinic-hero-meta {
            display: grid;
            gap: 0.75rem;
            align-content: start;
            padding: 1.2rem;
            border-radius: 1.5rem;
            background: rgba(255, 244, 229, 0.08);
            border: 1px solid rgba(255, 244, 229, 0.14);
            backdrop-filter: blur(8px);
        }

        .clinic-hero-meta strong {
            display: block;
            font-size: 2rem;
            line-height: 1;
            margin-bottom: 0.2rem;
        }

        .clinic-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .clinic-panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 1.6rem;
            padding: 1.15rem;
            box-shadow: 0 10px 30px rgba(59, 43, 28, 0.05);
        }

        .clinic-panel.highlight {
            outline: 2px solid rgba(201, 101, 43, 0.3);
            outline-offset: 4px;
        }

        .clinic-panel h2,
        .clinic-panel h3 {
            font-family: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
            letter-spacing: -0.03em;
            color: #23160f;
        }

        .clinic-panel p,
        .clinic-panel li,
        .clinic-panel label,
        .clinic-panel span {
            color: var(--muted);
        }

        .clinic-stack {
            display: grid;
            gap: 1rem;
        }

        .clinic-columns {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .clinic-cards {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
        }

        .clinic-card {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(41, 31, 22, 0.08);
            border-radius: 1.25rem;
            padding: 1rem;
        }

        .clinic-card strong {
            display: block;
            margin-top: 0.25rem;
            font-size: 1.7rem;
            line-height: 1;
            color: #1d130c;
        }

        .clinic-card a {
            color: #1d130c;
            font-weight: 600;
            text-decoration: none;
        }

        .clinic-form {
            display: grid;
            gap: 0.9rem;
        }

        .clinic-form-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .clinic-field {
            display: grid;
            gap: 0.4rem;
        }

        .clinic-field input,
        .clinic-field select,
        .clinic-field textarea {
            width: 100%;
            border: 1px solid rgba(61, 47, 34, 0.16);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.86);
            color: #24160f;
            padding: 0.8rem 0.9rem;
        }

        .clinic-field textarea {
            min-height: 8rem;
            resize: vertical;
        }

        .clinic-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: 0;
            border-radius: 999px;
            padding: 0.85rem 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #281a12, #c9652b);
            color: #fff8ef;
            cursor: pointer;
        }

        .clinic-button.secondary {
            background: rgba(36, 27, 20, 0.08);
            color: #24160f;
            border: 1px solid rgba(51, 38, 28, 0.1);
        }

        .clinic-inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .clinic-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(36, 27, 20, 0.08);
            color: #2d1c11;
        }

        .clinic-badge.pending {
            background: rgba(201, 101, 43, 0.12);
            color: #8f4417;
        }

        .clinic-badge.paid,
        .clinic-badge.completed {
            background: rgba(61, 132, 94, 0.16);
            color: #215539;
        }

        .clinic-badge.failed,
        .clinic-badge.cancelled {
            background: rgba(173, 63, 58, 0.14);
            color: #8f2e2b;
        }

        .clinic-list {
            display: grid;
            gap: 0.85rem;
        }

        .clinic-list-item {
            display: grid;
            gap: 0.4rem;
            padding: 1rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(51, 38, 28, 0.08);
            background: rgba(255, 255, 255, 0.62);
        }

        .clinic-doctor-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
        }

        .clinic-doctor-card {
            padding: 1rem;
            border-radius: 1.2rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(251, 243, 232, 0.92));
            border: 1px solid rgba(51, 38, 28, 0.08);
        }

        .clinic-doctor-card ul {
            display: grid;
            gap: 0.35rem;
            margin-top: 0.75rem;
        }

        .clinic-flash,
        .clinic-errors {
            margin-top: 1rem;
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            border: 1px solid transparent;
        }

        .clinic-flash {
            background: rgba(70, 139, 98, 0.12);
            border-color: rgba(70, 139, 98, 0.18);
            color: #1f5b38;
        }

        .clinic-errors {
            background: rgba(186, 82, 58, 0.1);
            border-color: rgba(186, 82, 58, 0.16);
            color: #8b2e1f;
        }

        .clinic-section-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }

        .clinic-muted {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        @media (max-width: 1024px) {
            .clinic-hero,
            .clinic-columns,
            .clinic-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="clinic-shell">
        <section class="clinic-hero">
            <div>
                <span class="clinic-kicker">Clínica digital • Stripe sandbox activo</span>
                <h1 class="clinic-title">Tu operación ya tiene portal, agenda y muro de pago.</h1>
                <p class="clinic-copy">
                    Este panel dejó de ser un placeholder. Ahora centraliza acceso administrativo, agenda médica
                    y la experiencia del paciente para reservar citas y simular pagos de prueba.
                </p>
            </div>

            <div class="clinic-hero-meta">
                <div>
                    <span class="clinic-kicker">Sesión</span>
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                </div>
                <div>
                    <span class="clinic-kicker">Roles</span>
                    <strong>{{ $user->roles->pluck('name')->implode(' / ') ?: 'sin rol' }}</strong>
                    <span>Ruta principal pensada para operación interna y portal paciente.</span>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="clinic-flash">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="clinic-errors">
                <strong>Hay acciones que necesitan corrección.</strong>
                <ul style="margin-top: 0.6rem; display: grid; gap: 0.35rem;">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="clinic-grid">
            @if ($user->hasRole('admin'))
                <section class="clinic-panel clinic-stack">
                    <div class="clinic-section-heading">
                        <div>
                            <h2 class="text-3xl">Centro de gestión</h2>
                            <p class="clinic-muted">Accesos rápidos para operar usuarios, pacientes, citas y agenda.</p>
                        </div>
                        <a class="clinic-button secondary" href="{{ url('/admin') }}">Abrir panel admin</a>
                    </div>

                    <div class="clinic-cards">
                        <div class="clinic-card">
                            <span class="clinic-badge">Usuarios</span>
                            <strong>{{ $adminStats['usuarios'] }}</strong>
                            <p class="clinic-muted">Cuentas activas para acceso al sistema.</p>
                            <a href="{{ url('/admin/users') }}">Gestionar usuarios</a>
                        </div>
                        <div class="clinic-card">
                            <span class="clinic-badge">Pacientes</span>
                            <strong>{{ $adminStats['pacientes'] }}</strong>
                            <p class="clinic-muted">Expedientes enlazables con su cuenta de acceso.</p>
                            <a href="{{ url('/admin/patients') }}">Vincular perfiles</a>
                        </div>
                        <div class="clinic-card">
                            <span class="clinic-badge">Médicos</span>
                            <strong>{{ $adminStats['medicos'] }}</strong>
                            <p class="clinic-muted">Profesionales con horarios configurables.</p>
                            <a href="{{ url('/admin/doctor-schedules') }}">Configurar horarios</a>
                        </div>
                        <div class="clinic-card">
                            <span class="clinic-badge">Citas hoy</span>
                            <strong>{{ $adminStats['citas_hoy'] }}</strong>
                            <p class="clinic-muted">Carga operativa del día actual.</p>
                            <a href="{{ url('/admin/appointments') }}">Abrir agenda</a>
                        </div>
                        <div class="clinic-card">
                            <span class="clinic-badge pending">Pagos pendientes</span>
                            <strong>{{ $adminStats['pagos_pendientes'] }}</strong>
                            <p class="clinic-muted">Reservas creadas que aún no han completado pago.</p>
                            <a href="{{ route('dashboard', ['focus' => 'payments']) }}">Ir al muro</a>
                        </div>
                    </div>
                </section>
            @endif

            @if ($user->hasRole('medico'))
                <section class="clinic-panel clinic-stack">
                    <div class="clinic-section-heading">
                        <div>
                            <h2 class="text-3xl">Vista médica</h2>
                            <p class="clinic-muted">Tus próximas citas aparecen aquí para no depender del panel admin.</p>
                        </div>
                    </div>

                    @if ($doctorAppointments->isEmpty())
                        <p class="clinic-muted">No tienes citas próximas cargadas en este momento.</p>
                    @else
                        <div class="clinic-list">
                            @foreach ($doctorAppointments as $appointment)
                                <article class="clinic-list-item">
                                    <div class="clinic-inline-actions">
                                        <strong style="color: #1d130c;">{{ $appointment->patient?->name ?? 'Paciente sin nombre' }}</strong>
                                        <span class="clinic-badge {{ $appointment->status }}">{{ $appointment->status }}</span>
                                        @if ($appointment->payment)
                                            <span class="clinic-badge {{ $appointment->payment->status }}">{{ $appointment->payment->status }}</span>
                                        @endif
                                    </div>
                                    <p class="clinic-muted">
                                        {{ $appointment->date_time_begin->format('d/m/Y H:i') }}
                                        —
                                        {{ $appointment->date_time_end->format('H:i') }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($user->hasRole('paciente'))
                <div class="clinic-columns">
                    <section class="clinic-panel clinic-stack">
                        <div class="clinic-section-heading">
                            <div>
                                <h2 class="text-3xl">Perfil del paciente</h2>
                                <p class="clinic-muted">
                                    Este formulario mantiene sincronizados el expediente y la cuenta de acceso.
                                </p>
                            </div>
                        </div>

                        <form class="clinic-form" method="POST" action="{{ route('dashboard.patient-profile.update') }}">
                            @csrf
                            @method('PATCH')

                            <div class="clinic-form-grid">
                                <label class="clinic-field">
                                    <span>Nombre completo</span>
                                    <input type="text" name="name" value="{{ old('name', $patient?->name ?? $user->name) }}" required>
                                </label>
                                <label class="clinic-field">
                                    <span>Correo de acceso</span>
                                    <input type="email" name="email" value="{{ old('email', $patient?->email ?? $user->email) }}" required>
                                </label>
                                <label class="clinic-field">
                                    <span>Teléfono</span>
                                    <input type="text" name="phone" value="{{ old('phone', $patient?->phone) }}">
                                </label>
                                <label class="clinic-field">
                                    <span>Fecha de nacimiento</span>
                                    <input type="date" name="birth_date" value="{{ old('birth_date', $patient?->birth_date) }}">
                                </label>
                                <label class="clinic-field">
                                    <span>Género</span>
                                    <select name="gender">
                                        <option value="">Selecciona</option>
                                        @foreach (['male' => 'Masculino', 'female' => 'Femenino', 'other' => 'Otro'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('gender', $patient?->gender) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <label class="clinic-field">
                                <span>Dirección</span>
                                <textarea name="address">{{ old('address', $patient?->address) }}</textarea>
                            </label>

                            <div class="clinic-inline-actions">
                                <button class="clinic-button" type="submit">Guardar perfil</button>
                                <span class="clinic-muted">El correo actualizado también será tu usuario de login.</span>
                            </div>
                        </form>
                    </section>

                    <section class="clinic-panel clinic-stack">
                        <div class="clinic-section-heading">
                            <div>
                                <h2 class="text-3xl">Reservar cita</h2>
                                <p class="clinic-muted">
                                    Selecciona médico y horario. El sistema abre de inmediato el pago de prueba en Stripe.
                                </p>
                            </div>
                            <span class="clinic-badge pending">{{ $money($appointmentPrice) }} por cita</span>
                        </div>

                        <form class="clinic-form" method="POST" action="{{ route('dashboard.appointments.store') }}">
                            @csrf

                            <div class="clinic-form-grid">
                                <label class="clinic-field">
                                    <span>Médico</span>
                                    <select name="doctor_id" id="doctor_id" required>
                                        <option value="">Selecciona un médico</option>
                                        @foreach ($doctors as $doctor)
                                            <option
                                                value="{{ $doctor->id }}"
                                                data-slot-duration="{{ $doctor->schedules->first()?->slot_duration ?? 30 }}"
                                                @selected((string) old('doctor_id') === (string) $doctor->id)
                                            >
                                                {{ $doctor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="clinic-field">
                                    <span>Inicio</span>
                                    <input type="datetime-local" name="date_time_begin" id="date_time_begin" value="{{ old('date_time_begin') ? \Illuminate\Support\Carbon::parse(old('date_time_begin'))->format('Y-m-d\TH:i') : '' }}" required>
                                </label>
                                <label class="clinic-field">
                                    <span>Fin</span>
                                    <input type="datetime-local" name="date_time_end" id="date_time_end" value="{{ old('date_time_end') ? \Illuminate\Support\Carbon::parse(old('date_time_end'))->format('Y-m-d\TH:i') : '' }}" required>
                                </label>
                            </div>

                            <div class="clinic-inline-actions">
                                <button class="clinic-button" type="submit">Apartar cita y abrir pago</button>
                                <span class="clinic-muted">Si el slot ya no está libre, el formulario te lo indicará antes de cobrar.</span>
                            </div>
                        </form>

                        <div class="clinic-doctor-grid">
                            @foreach ($doctors as $doctor)
                                <article class="clinic-doctor-card">
                                    <div class="clinic-inline-actions" style="justify-content: space-between;">
                                        <strong style="color: #1d130c;">{{ $doctor->name }}</strong>
                                        <span class="clinic-badge">{{ $doctor->schedules->first()?->slot_duration ?? 30 }} min</span>
                                    </div>

                                    @if ($doctor->schedules->isEmpty())
                                        <p class="clinic-muted" style="margin-top: 0.75rem;">Sin horarios visibles todavía.</p>
                                    @else
                                        <ul>
                                            @foreach ($doctor->schedules as $schedule)
                                                <li>
                                                    {{ $dayNames[$schedule->day_of_week] ?? 'Día' }}
                                                    ·
                                                    {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                                    -
                                                    {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>

                <section id="payment-wall" class="clinic-panel clinic-stack {{ $focus === 'payments' ? 'highlight' : '' }}">
                    <div class="clinic-section-heading">
                        <div>
                            <h2 class="text-3xl">Muro de pago</h2>
                            <p class="clinic-muted">
                                Cada cita genera un PaymentIntent sandbox. Desde aquí puedes simular un pago exitoso o fallido.
                            </p>
                        </div>
                    </div>

                    @if ($payments->isEmpty())
                        <p class="clinic-muted">Todavía no tienes pagos asociados. Reserva una cita para abrir el muro.</p>
                    @else
                        <div class="clinic-list">
                            @foreach ($payments as $payment)
                                <article class="clinic-list-item">
                                    <div class="clinic-inline-actions" style="justify-content: space-between;">
                                        <div>
                                            <strong style="color: #1d130c;">{{ $money($payment->amount) }} · {{ strtoupper($payment->currency) }}</strong>
                                            <p class="clinic-muted">
                                                {{ $payment->appointment?->doctor?->name ?? 'Doctor sin asignar' }}
                                                ·
                                                {{ $payment->appointment?->date_time_begin?->format('d/m/Y H:i') ?? 'sin horario' }}
                                            </p>
                                        </div>
                                        <div class="clinic-inline-actions">
                                            <span class="clinic-badge {{ $payment->status }}">{{ $payment->status }}</span>
                                            @if ($payment->appointment)
                                                <span class="clinic-badge {{ $payment->appointment->status }}">{{ $payment->appointment->status }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="clinic-muted">
                                        PaymentIntent:
                                        <strong style="color: #1d130c;">{{ $payment->stripe_payment_intent_id ?? 'pendiente de generar' }}</strong>
                                    </p>

                                    @if (($dashboardPayment['payment_id'] ?? null) === $payment->id)
                                        <div class="clinic-card" style="margin-top: 0.35rem;">
                                            <span class="clinic-badge">Último intento creado</span>
                                            <p class="clinic-muted" style="margin-top: 0.55rem;">
                                                `client_secret`: {{ $dashboardPayment['client_secret'] ?? 'no disponible' }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($payment->status !== 'paid')
                                        <form class="clinic-form" method="POST" action="{{ route('dashboard.payments.simulate', $payment) }}">
                                            @csrf

                                            <div class="clinic-form-grid">
                                                <label class="clinic-field">
                                                    <span>Escenario de prueba</span>
                                                    <select name="payment_method">
                                                        <option value="pm_card_visa">Pago exitoso · `pm_card_visa`</option>
                                                        <option value="pm_card_chargeDeclined">Tarjeta rechazada · `pm_card_chargeDeclined`</option>
                                                    </select>
                                                </label>
                                            </div>

                                            <div class="clinic-inline-actions">
                                                <button class="clinic-button" type="submit">Simular pago</button>
                                                <span class="clinic-muted">
                                                    Usa este botón en local y testing para mover el pago sin frontend Stripe completo.
                                                </span>
                                            </div>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="clinic-panel clinic-stack">
                    <div class="clinic-section-heading">
                        <div>
                            <h2 class="text-3xl">Tus citas</h2>
                            <p class="clinic-muted">Resumen clínico y de cobro en una sola vista.</p>
                        </div>
                    </div>

                    @if ($appointments->isEmpty())
                        <p class="clinic-muted">Aún no tienes citas registradas.</p>
                    @else
                        <div class="clinic-list">
                            @foreach ($appointments as $appointment)
                                <article class="clinic-list-item">
                                    <div class="clinic-inline-actions" style="justify-content: space-between;">
                                        <div>
                                            <strong style="color: #1d130c;">{{ $appointment->doctor?->name ?? 'Doctor sin asignar' }}</strong>
                                            <p class="clinic-muted">
                                                {{ $appointment->date_time_begin->format('d/m/Y H:i') }}
                                                —
                                                {{ $appointment->date_time_end->format('H:i') }}
                                            </p>
                                        </div>
                                        <div class="clinic-inline-actions">
                                            <span class="clinic-badge {{ $appointment->status }}">{{ $appointment->status }}</span>
                                            @if ($appointment->payment)
                                                <span class="clinic-badge {{ $appointment->payment->status }}">{{ $appointment->payment->status }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>

    <script>
        const doctorField = document.getElementById('doctor_id');
        const startField = document.getElementById('date_time_begin');
        const endField = document.getElementById('date_time_end');

        const syncAppointmentEnd = () => {
            if (!doctorField || !startField || !endField || !startField.value) {
                return;
            }

            const selected = doctorField.options[doctorField.selectedIndex];
            const duration = Number(selected?.dataset?.slotDuration || 30);
            const startDate = new Date(startField.value);

            if (Number.isNaN(startDate.getTime())) {
                return;
            }

            const endDate = new Date(startDate.getTime() + duration * 60 * 1000);
            endField.value = endDate.toISOString().slice(0, 16);
        };

        doctorField?.addEventListener('change', syncAppointmentEnd);
        startField?.addEventListener('change', syncAppointmentEnd);
    </script>
</x-layouts::app>
