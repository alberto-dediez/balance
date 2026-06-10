Feature:
    I want to make sure that the software allow to register an alert configuration

    Scenario: As an admin, I can access to register new alert configurations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/alert-configurations/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new alert configurations
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/alert-configurations/register"
        Then the response status should be 403
