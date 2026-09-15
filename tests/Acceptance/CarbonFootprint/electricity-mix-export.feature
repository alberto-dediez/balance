Feature:
    Ability to export electricity mixes as spreadsheet files

    Scenario: As an admin I can access the electricity mixes export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the electricity mixes export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/electricity_mixes/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed electricity mixes exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/export/333/download"
        Then the response status should be 200
