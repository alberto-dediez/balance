Feature:
    Ability to export processes as spreadsheet files

    Scenario: As an admin I can access the processes export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/processes/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the processes export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/processes/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed processes exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "processes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/processes/export/333/download"
        Then the response status should be 200
