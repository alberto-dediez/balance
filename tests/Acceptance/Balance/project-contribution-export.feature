Feature:
    Ability to export the contributions made to the projects, with the contributor personal data, as spreadsheet files

    Background:
        Given account "MyCompany" with id "666" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"

    Scenario: As an owner I see the export button on the contributions list
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/contributions"
        Then the response status should be 200
        Then I see a text "Exportar"

    Scenario: As an owner I can access the contributions export screen
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/project_contributions/export"
        Then the response status should be 200
        Then I see a text "Aportaciones a proyectos"
        Then I see a text "Email"

    Scenario: As a normal user I cannot access the contributions export screen
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/project_contributions/export"
        Then the response status should be 403

    Scenario: As an admin I can access the contributions export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/project_contributions/export"
        Then the response status should be 200
        Then I see a text "Cuenta"

    Scenario: As an admin I can download a completed contributions exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "project_contributions" from account "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/project_contributions/export/333/download"
        Then the response status should be 200
