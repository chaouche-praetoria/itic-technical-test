<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à rejoindre une classe - ITIC Paris</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
                    <tr>
                        <td align="center" style="background-color:#0f172a; padding:40px;">
                            <h1 style="color:#ffffff; margin:0; font-size:24px; font-weight:800; letter-spacing:-0.025em;">ITIC Paris</h1>
                            <p style="color:#94a3b8; margin:8px 0 0; font-size:13px; text-transform:uppercase; letter-spacing:0.1em;">Invitation à une classe</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#0f172a; font-size:16px; line-height:1.6; margin:0 0 16px;">Bonjour,</p>
                            <p style="color:#334155; font-size:15px; line-height:1.6; margin:0 0 24px;">
                                Vous êtes invité(e) à rejoindre la classe
                                <strong>{{ $classroom->name }}</strong>
                                @if($classroom->academicLevel)
                                    (niveau {{ $classroom->academicLevel->name }})
                                @endif
                                @if($classroom->teacher)
                                    , créée par {{ $classroom->teacher->name }}.
                                @else
                                    .
                                @endif
                            </p>
                            <p style="color:#334155; font-size:15px; line-height:1.6; margin:0 0 32px;">
                                Cliquez sur le bouton ci-dessous pour renseigner vos informations et intégrer la classe.
                            </p>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $joinUrl }}" style="display:inline-block; background-color:#4f46e5; color:#ffffff; text-decoration:none; padding:16px 40px; border-radius:12px; font-weight:700; font-size:15px;">Rejoindre la classe</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color:#94a3b8; font-size:12px; line-height:1.6; margin:32px 0 0; word-break:break-all;">
                                Ou copiez ce lien dans votre navigateur :<br>
                                <a href="{{ $joinUrl }}" style="color:#4f46e5;">{{ $joinUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc; padding:24px 40px; text-align:center;">
                            <p style="color:#94a3b8; font-size:12px; margin:0;">ITIC Paris — Plateforme d'évaluation</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
