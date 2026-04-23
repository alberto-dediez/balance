Feature:
    I want to make sure that the system allow to change my password

    Scenario: As normal user I can change my password
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "oldPassword:apassword|[password][first]:1234|[password][second]:1234" and button "Guardar"
        Then the response status should be 200
        Then I see a text "La contraseña se ha actualizado correctamente"
        