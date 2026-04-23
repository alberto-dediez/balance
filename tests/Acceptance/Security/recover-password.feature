Feature:
    I want to make sure that I can recover my password if a lost it

    Scenario: As logged user I can't reset my password
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/recover-password"
        Then the response status should be 302

    Scenario: As anonymous user I can reset my password
        Given user "forgot@dediez.software" with id "123" exists
        When I go to "/es/recover-password"
        When I submit a form with "username:forgot@dediez.software" and button "Recuperar contraseña"
        Then I received an email with subject "Instrucciones para recuperar contraseña" to "forgot@dediez.software"

    Scenario: As anonymous user I can modify my password
        Given user "user@dediez.software" with recover token "1111-2222" exists
        When I go to "/es/recover-password/1111-2222"
        When I submit a form with "[password][first]:1234|[password][second]:1234" and button "Modificar contraseña"
        Then the response status should be 200
        Then I see a text "Se ha establecido la nueva contraseña"
        