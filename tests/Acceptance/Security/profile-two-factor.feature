Feature:
    Users configure their second factor from their profile

    Scenario: The profile menu links to the second factor page
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        Then I see a link with name "Segundo factor Verificación en dos pasos"
        When I go to "/es/profile/two-factor"
        Then the response status should be 200
        Then I see a text "No activado"
        Then I do not see a link with name "Vincular otro dispositivo"

    Scenario: A user enables the email second factor
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        Then I see a link with name "Email"
        When I go to "/es/profile/two-factor/email" with csrf token "two-factor-email"
        Then I am redirected to "/es/profile/two-factor"
        Then I see a text "Activado"
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: A user enables the authenticator app
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/totp" with csrf token "two-factor-totp"
        Then I am redirected to "/es/profile/two-factor/confirm"
        Then I see a text "Escanea"
        Then I see a text "INMEMORYSECRET"
        Then user "user@dediez.software" has no two factor method
        When I go to "/es/profile/two-factor/confirm"
        When I submit a form with "code:123456" and button "Verificar"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has two factor method "Totp"

    Scenario: A wrong code does not enable the authenticator app
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/totp" with csrf token "two-factor-totp"
        Then I am redirected to "/es/profile/two-factor/confirm"
        When I go to "/es/profile/two-factor/confirm"
        When I submit a form with "code:000000" and button "Verificar"
        Then the response status should be 200
        Then I see a text "Te quedan 2 intentos"
        Then user "user@dediez.software" has no two factor method

    Scenario: A user cancels the activation of the authenticator app
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/totp" with csrf token "two-factor-totp"
        Then I am redirected to "/es/profile/two-factor/confirm"
        When I go to "/es/profile/two-factor/cancel" with csrf token "two-factor-cancel"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has no two factor method
        When I go to "/es/profile/two-factor/confirm"
        Then I am redirected to "/es/profile/two-factor"

    Scenario: A user with the authenticator app only sees the actions available
        Given user "user@dediez.software" with two factor method "Totp" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        Then I see a text "Activado"
        Then I see a link with name "Volver"
        Then I see a link with name "Desactivar segundo factor"
        Then I see a link with name "Email"
        Then I do not see a link with name "Aplicación de autenticación"
        Then I see a link with name "Vincular otro dispositivo"

    Scenario: Cancelling the link of another device keeps the current authenticator app
        Given user "user@dediez.software" with two factor method "Totp" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/totp" with csrf token "two-factor-totp"
        Then I am redirected to "/es/profile/two-factor/confirm"
        When I go to "/es/profile/two-factor/cancel" with csrf token "two-factor-cancel"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has two factor method "Totp"

    Scenario: A user switches from the authenticator app to email
        Given user "user@dediez.software" with two factor method "Totp" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/email" with csrf token "two-factor-email"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: Cancelling the switch to the authenticator app keeps the current method
        Given user "user@dediez.software" with two factor method "Email" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        When I go to "/es/profile/two-factor/totp" with csrf token "two-factor-totp"
        Then I am redirected to "/es/profile/two-factor/confirm"
        When I go to "/es/profile/two-factor/cancel" with csrf token "two-factor-cancel"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: Enabling a method requires the csrf token
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor/email"
        Then the response status should be 403
        Then user "user@dediez.software" has no two factor method

    Scenario: A user disables the second factor in optional mode
        Given user "user@dediez.software" with two factor method "Email" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        Then I see a link with name "Desactivar segundo factor"
        When I go to "/es/profile/two-factor/disable" with csrf token "two-factor-disable"
        Then I am redirected to "/es/profile/two-factor"
        Then user "user@dediez.software" has no two factor method

    Scenario: Disabling the second factor requires the csrf token
        Given user "user@dediez.software" with two factor method "Email" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor/disable"
        Then the response status should be 403
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: The second factor cannot be disabled in required mode
        Given two factor authentication is required
        And user "user@dediez.software" with two factor method "Email" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor/disable" with csrf token "two-factor-disable"
        Then the response status should be 403
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: In required mode the page shows the second factor is required
        Given two factor authentication is required
        And user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/two-factor"
        Then the response status should be 200
        Then I see a text "Obligatorio"
        Then I do not see a text "Desactivar segundo factor"
