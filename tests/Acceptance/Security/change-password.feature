Feature:
    I want to make sure that the system allow to change my password

  Scenario: As normal user I can change my password
    Given user "user@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/profile/change-password"
    When I submit a form with "oldPassword:apassword|[password][first]:1234|[password][second]:1234" and button "Guardar"
    Then the response status should be 200
    Then I see a text "La contraseña se ha actualizado correctamente"

  Scenario: Changing my password does not affect my api tokens
    Given user "user@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user@dediez.software" has an api token "hyd_after_password_change_0000000000000000000" named "Integration"
    When I am logged as "user@dediez.software"
    When I go to "/es/profile/change-password"
    When I submit a form with "oldPassword:apassword|[password][first]:1234|[password][second]:1234" and button "Guardar"
    Then I see a text "La contraseña se ha actualizado correctamente"
    Then user "user@dediez.software" should have 1 active api tokens
    When I send a POST request to "/api/login" with body:
    """
    {
        "username": "user@dediez.software",
        "password": "hyd_after_password_change_0000000000000000000"
    }
    """
    Then the status code should be 200
