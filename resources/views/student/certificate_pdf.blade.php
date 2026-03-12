<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #fff;
            padding: 0;
            margin: 0;
        }

        .certificate {
            border: 8px solid #4f46e5;
            border-radius: 16px;
            padding: 60px 80px;
            text-align: center;
            width: 90%;
            margin: 40px auto;
        }

        .label {
            color: #a5b4fc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 20px;
        }

        .name {
            font-size: 42px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 20px 0;
        }

        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin: 10px 0;
        }

        .course {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin: 15px 0;
        }

        .date {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 30px;
        }

        .code {
            font-size: 11px;
            color: #d1d5db;
            margin-top: 8px;
        }

        .divider {
            border: none;
            border-top: 2px solid #e0e7ff;
            margin: 30px auto;
            width: 60%;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <p class="label">Certificado de finalización</p>
        <hr class="divider">
        <p class="subtitle">Este certificado se otorga a</p>
        <p class="name">{{ $user->name }}</p>
        <p class="subtitle">por haber completado exitosamente el curso</p>
        <p class="course">{{ $course->title }}</p>
        <hr class="divider">
        <p class="date">Emitido el {{ $certificate->issued_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
        <p class="code">Código: {{ $certificate->code }}</p>
    </div>
</body>

</html>