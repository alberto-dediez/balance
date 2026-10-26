Feature:
    I want to make sure that every email sent by the platform is logged

    Scenario: The password recovery email is logged
        Given user "forgot@dediez.software" with id "123" exists
        When I go to "/es/recover-password"
        When I submit a form with "username:forgot@dediez.software" and button "Recuperar contraseña"
        Then I received an email with subject "Instrucciones para recuperar contraseña" to "forgot@dediez.software"
        Then there is 1 email log to "forgot@dediez.software" with status "Sent"

    Scenario: The two factor code email is logged
        Given user "user@dediez.software" with two factor method "Email" exists
        When I sign in with "user@dediez.software" and "apassword"
        Then I am redirected to "/es/login/2fa"
        Then I received an email with subject "Código de acceso" to "user@dediez.software"
        Then there is 1 email log to "user@dediez.software" with status "Sent"
