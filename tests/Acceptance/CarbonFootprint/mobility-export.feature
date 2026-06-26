Feature:
    Ability to export mobilities as spreadsheet files

    Scenario: As an admin I can access the mobilities export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/mobilities/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the mobilities export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/mobilities/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed mobilities exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "mobilities" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/mobilities/export/333/download"
        Then the response status should be 200
