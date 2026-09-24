Feature:
    Users confirm their identity with a second factor after the password

    Scenario: A user without second factor signs in directly in optional mode
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/"

    Scenario: A user with email second factor must enter the code sent by email
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then the response status should be 200
        Then I see a text "Verificación en dos pasos"
        Then I see a text "u***@dediez.software"
        Then I received an email with subject "Código de acceso" to "user@dediez.software"
        Then going to "/es/profile" redirects to "/es/login/2fa"
        When I go to "/es/login/2fa"
        When I submit the two factor code from the last email
        Then I am redirected to "/es/"
        Then the response status should be 200
        When I go to "/es/profile"
        Then the response status should be 200

    Scenario: An invalid csrf token shows an error instead of redirecting silently
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I submit a two factor code with an invalid csrf token
        Then the response status should be 200
        Then I see a text "No se ha podido validar el formulario"

    Scenario: In required mode a user without method receives the code by email
        Given two factor authentication is required
        And user "user@dediez.software" with role "ROLE_USER" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then I received an email with subject "Código de acceso" to "user@dediez.software"

    Scenario: A wrong code shows the attempts left
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I submit a wrong two factor code
        Then the response status should be 200
        Then I see a text "Código incorrecto"
        Then I see a text "Te quedan 2 intentos"

    Scenario: Three wrong codes return to the login
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I submit a wrong two factor code
        When I submit a wrong two factor code
        When I submit a wrong two factor code
        Then I am redirected to "/es/login"
        Then I see a text "La verificación en dos pasos ha fallado. Vuelve a identificarte."
        When I go to "/es/login/2fa"
        Then I am redirected to "/es/login"

    Scenario: Resending generates a new code
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I resend the two factor code
        Then the response status should be 200
        Then I see a text "Se ha enviado un nuevo código."
        When I submit the two factor code from the last email
        Then I am redirected to "/es/"

    Scenario: A user with authenticator app enters the code of the app
        Given user "user@dediez.software" with two factor method "Totp" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then I see a text "Introduce el código de tu aplicación de autenticación."
        Then I do not see a text "Reenviar"
        When I submit the valid authenticator code
        Then I am redirected to "/es/"
        Then the response status should be 200

    Scenario: An unreadable authenticator secret falls back to email
        Given user "user@dediez.software" with an unreadable authenticator secret exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then I see a text "no está disponible"
        Then I received an email with subject "Código de acceso" to "user@dediez.software"
        When I submit the two factor code from the last email
        Then I am redirected to "/es/"
        Then user "user@dediez.software" has two factor method "Totp"

    Scenario: The page requested before signing in is respected
        Given user "user@dediez.software" with two factor method "Email" exists
        And the page "/es/profile" was requested before signing in
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I submit the two factor code from the last email
        Then I am redirected to "/es/profile"

    Scenario: The user cannot be switched while the verification is pending
        Given user "user@dediez.software" with two factor method "Email" exists
        And user "other@dediez.software" with role "ROLE_USER" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then going to "/es/?_switch_user=other@dediez.software" redirects to "/es/login/2fa"

    Scenario: Signing out during the verification cancels it
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I go to "/es/logout" with csrf token "logout" as "_csrf_token"
        Then the response status should be 302
        When I go to "/es/login/2fa"
        Then I am redirected to "/es/login"

    Scenario: SSO does not ask for the second factor when the SSO flag is disabled
        Given user "user@dediez.software" with two factor method "Totp" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/"

    Scenario: SSO does not ask for the second factor with the email method
        Given two factor authentication for SSO is enabled
        And user "user@dediez.software" with two factor method "Email" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/"

    Scenario: SSO does not ask for the second factor to a user without method in required mode
        Given two factor authentication for SSO is enabled
        And two factor authentication is required
        And user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/"

    Scenario: SSO asks for the authenticator code when the SSO flag is enabled
        Given two factor authentication for SSO is enabled
        And user "user@dediez.software" with two factor method "Totp" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then the response has no remember me cookie
        Then I am redirected to "/es/login/2fa"
        When I submit the valid authenticator code
        Then the response has a remember me cookie
        Then I am redirected to "/es/"

    Scenario: SSO does not ask for the second factor with an unreadable authenticator secret
        Given two factor authentication for SSO is enabled
        And user "user@dediez.software" with an unreadable authenticator secret exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/"

    Scenario: The remember me cookie is only issued after the second factor
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword" remembering me
        Then the response has no remember me cookie
        Then I am redirected to "/es/login/2fa"
        When I submit the two factor code from the last email
        Then the response has a remember me cookie

    Scenario: A later access with the remember me cookie does not ask for the second factor
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword" remembering me
        Then I am redirected to "/es/login/2fa"
        When I submit the two factor code from the last email
        Then the response has a remember me cookie
        When my session expires
        And I go to "/es/profile"
        Then the response status should be 200

    Scenario: Without remember me no cookie is issued after the second factor
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        When I submit the two factor code from the last email
        Then the response has no remember me cookie
