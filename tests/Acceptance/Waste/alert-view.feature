Feature:
    Ability to view an alert in web

    Scenario: As owner I can view one of my alerts
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given alert with id "alert-1" for account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/alerts/view/alert-1"
        Then the response status should be 200

    Scenario: As admin I can view any alert
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given alert with id "alert-1" for account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/alerts/view/alert-1"
        Then the response status should be 200

    Scenario: As normal user I can't view an alert
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given alert with id "alert-1" for account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/alerts/view/alert-1"
        Then the response status should be 403

    Scenario: As owner I can't view another account's alert
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given alert with id "alert-1" for account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/alerts/view/alert-1"
        Then the response status should be 403
