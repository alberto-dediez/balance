Feature:
    Ability to browse and manage the folders of the media library from the files list

    Scenario: As an owner I see and manage the folders of my account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see the folder tiles "Sin carpeta, Contratos, Facturas, Todas las carpetas"
        Then the folder tile "Sin carpeta" is active
        Then I do not see a text "Ajena"
        Then I see a text "library/folders/register"
        Then I see a text "library/folders/edit/f-1"
        Then I see a text "library/folders/remove/f-1"

    Scenario: As a user I see the folders but I cannot manage them
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see the folder tiles "Sin carpeta, Contratos, Todas las carpetas"
        Then the folder tile "Sin carpeta" is active
        Then I do not see a text "library/folders/register"
        Then I do not see a text "library/folders/edit/"
        Then I do not see a text "library/folders/remove/"

    Scenario: As an owner without folders I am invited to create one
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a text "Todavía no hay carpetas"
        Then I see a text "library/folders/register"

    Scenario: As a user without folders I do not see the folders card
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I do not see a text "folder-tile"
        Then I do not see a text "Todavía no hay carpetas"

    Scenario: An empty folder is shown as a tile
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a text "folder-tile"
        Then I see a text "library/folders/edit/f-1"
        Then I do not see a text "Todavía no hay carpetas"

    Scenario: Entering a folder highlights its tile and titles the page
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then I do not see a text "library/media/upload?folder=f-1"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I see a text "Biblioteca &gt; Contratos"
        Then the folder tile "Contratos" is active
        Then I see a text "library/media/upload?folder=f-1"
        Then the active filters show "Carpeta: Contratos"
        When I filter the library by all the folders
        Then the response status should be 200
        Then I see a text "Biblioteca &gt; Todas las carpetas"
        Then the folder tile "Todas las carpetas" is active
        Then I do not see a text "library/media/upload?folder="

    Scenario: While impersonating an account I see the folders of that account
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "Acme" with id "a1" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        Given folder "Propia" with id "f-1" of account "admin-account-id" exists
        Given folder "Contratos Acme" with id "f-2" of account "a1" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "/es/library/media"
        Then I see the folder tiles "Sin carpeta, Contratos Acme, Todas las carpetas"
        Then I see a text "library/folders/edit/f-2"
        Then I do not see a text "Propia"
        Then I do not see a text "library/folders/edit/f-1"

    Scenario: The folders list screen does not exist anymore
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders"
        Then the response status should be 404

    Scenario: Uploading from a folder preselects it
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media/upload?folder=f-1"
        Then the response status should be 200
        Then the upload form has the folder "Contratos" selected

    Scenario: A folder of another account or an unknown folder is not preselected
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media/upload?folder=f-3"
        Then the response status should be 200
        Then the upload form has no folder selected
        When I go to "/es/library/media/upload?folder=f-9"
        Then the response status should be 200
        Then the upload form has no folder selected
