Feature:
    Ability to list alerts in web

    Scenario: As owner I can access a list of my alerts
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given alert with id "alert-1" for account "mycompany" exists
        Given alert with id "alert-2" for account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/alerts"
        Then the response status should be 200

    Scenario: As admin I can access alerts list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/alerts"
        Then the response status should be 200

    Scenario: As normal user I can't access alerts list
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/alerts"
        Then the response status should be 403
