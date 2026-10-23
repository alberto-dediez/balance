Feature:
    Ability to export entities as spreadsheet files

    Scenario: As admin I can access the export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/users/export"
        Then the response status should be 200
        Then I see a form field "[filter][account]"
        Then I see an option "Administrador" in form field "[filter][role]"

    Scenario: As a normal user I cannot access the export screen
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/users/export"
        Then the response status should be 403

    Scenario: An unknown export type returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/unknown/export"
        Then the response status should be 404

    Scenario: As an owner I do not see the account selector when exporting users
        Given account "mycompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/export"
        Then the response status should be 200
        Then I do not see a form field "[filter][account]"
        Then I see a form field "[filter][role]"
        Then I see an option "Propietario" in form field "[filter][role]"
        Then I do not see an option "Administrador" in form field "[filter][role]"

    Scenario: As an owner I can export the users of my account
        Given account "mycompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/export"
        When I submit a form with "" and button "Exportar"
        Then I see a text "La exportación se está generando"
