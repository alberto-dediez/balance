Feature:
    I want to make sure that the system is allow to access the details of an alert configuration

    Scenario: As an admin, I can access to edit alert configurations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given alert configuration with id "ac-333" for account "acc-1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/alert-configurations/edit/ac-333"
        Then the response status should be 200

    Scenario: As an owner, I can't access to edit alert configurations
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given alert configuration with id "ac-333" for account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/alert-configurations/edit/ac-333"
        Then the response status should be 403
