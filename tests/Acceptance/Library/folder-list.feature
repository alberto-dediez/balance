Feature:
    Ability to list the folders of the media library

    Scenario: As an owner I only see the folders of my account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "Contratos"
        Then I see a text "Facturas"
        Then I do not see a text "Ajena"
        Then I see a text "library/folders/register"

    Scenario: As a user I see the folders but I cannot manage them
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders"
        Then the response status should be 200
        Then I see a table with 2 rows
        Then I see a text "Contratos"
        Then I do not see a text "library/folders/register"
        Then I do not see a link with title "Editar"
        Then I do not see a link with title "Eliminar"

    Scenario: I can filter the folders by name
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders"
        When I filter by "name" with "contr"
        Then the response status should be 200
        Then I see a text "Contratos"
        Then I do not see a text "Facturas"
