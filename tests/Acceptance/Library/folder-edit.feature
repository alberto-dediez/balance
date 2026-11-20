Feature:
    Ability to rename the folders of the media library

    Scenario: As an owner I can rename a folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/edit/f-1"
        Then the response status should be 200
        When I submit a form with "name:Contratos firmados" and button "Guardar y salir"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Carpeta actualizada con éxito"
        Then I see a text "Contratos firmados"

    Scenario: A folder cannot take the name of another folder of the account
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/edit/f-1"
        When I submit a form with "name:Facturas" and button "Guardar y salir"
        Then the response status should be 200
        Then I see a text "Ya existe una carpeta con ese nombre"
        Then I do not see a text "Carpeta actualizada con éxito"

    Scenario: As a user I cannot rename folders
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders/edit/f-1"
        Then the response status should be 403

    Scenario: The folders of another account cannot be edited
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/edit/f-3"
        Then the response status should be 403

    Scenario: An unknown folder cannot be edited
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/edit/f-9"
        Then the response status should be 404
