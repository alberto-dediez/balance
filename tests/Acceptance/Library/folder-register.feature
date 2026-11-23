Feature:
    Ability to create folders in the media library

    Scenario: As an owner I can create a folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register"
        Then the response status should be 200
        When I submit a form with "name:Contratos" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Carpeta creada con éxito"
        Then I see a text "Contratos"
        Then the folder "Contratos" exists

    Scenario: As an admin without account I create a folder of the library without account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/folders/register"
        When I submit a form with "name:Plantillas" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Plantillas"

    Scenario: Two folders of the same account cannot share the name
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register"
        When I submit a form with "name:Contratos" and button "Crear"
        Then the response status should be 200
        Then I see a text "Ya existe una carpeta con ese nombre"
        Then I do not see a text "Carpeta creada con éxito"

    Scenario: Two accounts can have folders with the same name
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register"
        When I submit a form with "name:Contratos" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then I see a text "Carpeta creada con éxito"

    Scenario: As a user I cannot create folders
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders/register"
        Then the response status should be 403
