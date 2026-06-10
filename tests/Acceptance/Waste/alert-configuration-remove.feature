Feature:
    I want to make sure that the system is allow to remove an alert configuration

    Scenario: As an admin, I can remove alert configurations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given alert configuration with id "ac-333" for account "acc-1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/alert-configurations/remove/ac-333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la configuración de alerta"

    Scenario: As normal user I can't remove alert configurations
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given alert configuration with id "ac-333" for account "acc-1" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/alert-configurations/remove/ac-333"
        Then the response status should be 403
