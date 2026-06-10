Feature:
    Ability to list alert configurations in web

    Scenario: As admin I can access a list of all alert configurations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/alert-configurations"
        Then the response status should be 200

    Scenario: As normal user I can't access a list of all alert configurations
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/alert-configurations"
        Then the response status should be 403

    Scenario: As owner I can't access a list of all alert configurations
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/alert-configurations"
        Then the response status should be 403
