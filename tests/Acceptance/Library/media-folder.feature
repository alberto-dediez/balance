Feature:
    Ability to organise the files of the media library in folders

    Scenario: The library only lists the files without folder by default
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "logo.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a text "logo.png"
        Then I do not see a text "signed.png"

    Scenario: Filtering by a folder lists only its files
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "invoice.png" with id "m-3" in folder "f-2" of account "1111" exists
        Given media "logo.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "signed.png"
        Then I do not see a text "invoice.png"
        Then I do not see a text "logo.png"
        Then I see a text "<td>Contratos</td>"
        Then the active filters show "Carpeta: Contratos"

    Scenario: Filtering by all the folders lists the files with and without folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "logo.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by all the folders
        Then the response status should be 200
        Then I see a text "signed.png"
        Then I see a text "logo.png"
        Then the active filters show "Carpeta: Todas las carpetas"
        Then I see a text "<td>Contratos</td>"

    Scenario: Searching by name with all the folders finds the files of any folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "logo.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter by "name" with "signed"
        Then I do not see a text "signed.png"
        When I filter the library by all the folders
        Then I see a text "signed.png"
        Then I do not see a text "logo.png"

    Scenario: Resetting the filter goes back to the files without folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "logo.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then I do not see a text "logo.png"
        When I press "Resetear filtro"
        Then I see a text "logo.png"
        Then I do not see a text "signed.png"

    Scenario: I can move several files to a folder at once
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        Given media "banner.png" with id "m-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I move the media "m-1,m-2" to the folder "Contratos"
        Then the media "m-1" is in the folder "f-1"
        Then the media "m-2" is in the folder "f-1"
        When I follow redirect
        Then I see a text "Se ha movido el archivo m-1"
        Then I do not see a text "logo.png"
        Then I do not see a text "banner.png"

    Scenario: I can take several files out of their folder at once
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        When I move the media "m-1" out of their folder
        Then the media "m-1" is not in any folder
        When I go to "/es/library/media"
        When I press "Resetear filtro"
        Then I see a text "signed.png"

    Scenario: I can change the folder of a file while editing it
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media/edit/m-1"
        Then the response status should be 200
        Then I see a text "Carpeta"
        When I submit a form with "name:signed.png|folderId:f-2" and button "Guardar y salir"
        Then the media "m-1" is in the folder "f-2"
        When I go to "/es/library/media/edit/m-1"
        When I submit a form with "name:signed.png|folderId:" and button "Guardar y salir"
        Then the media "m-1" is not in any folder

    Scenario: I can upload files into a folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media/upload"
        When I submit the upload form with files "first.png,second.png" into the folder "Contratos"
        When I follow redirect
        Then I see a text "Archivos subidos correctamente"
        Then every file of the library is in the folder "f-1"

    Scenario: A user cannot move a file somebody else uploaded
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        When I move the media "m-1" to the folder "Contratos"
        When I follow redirect
        Then I see a text "No se ha podido mover el archivo m-1"
        Then the media "m-1" is not in any folder

    Scenario: The folder selectors only offer the folders of my account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then I see a text "Contratos"
        Then I do not see a text "Ajena"
        When I go to "/es/library/media/edit/m-1"
        Then I see a text "Contratos"
        Then I do not see a text "Ajena"

    Scenario: Moving a file to a folder of another account fails without changes
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media/edit/m-1"
        When I send the media "m-1" to the folder "f-3" through a forged edit form
        Then the media "m-1" is not in any folder

    Scenario: The files shared by other accounts are listed even when they are in a folder of their own
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Manuales" with id "f-3" of account "2222" exists
        Given media "own.png" with id "m-1" of account "1111" exists
        Given media "manual.png" with id "m-2" shared in the library of account "2222" in folder "f-3" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        When I filter the library including the shared media
        Then the response status should be 200
        Then I see a text "own.png"
        Then I see a text "manual.png"
        Then I do not see a text "Manuales"

    Scenario: While impersonating an account I use the folders of that account
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "Acme" with id "a1" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        Given folder "Propia" with id "f-1" of account "admin-account-id" exists
        Given folder "Contratos Acme" with id "f-2" of account "a1" exists
        Given media "logo.png" with id "m-1" of account "a1" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "/es/library/media"
        Then I see a text "Contratos Acme"
        Then I do not see a text "Propia"
        When I move the media "m-1" to the folder "Contratos Acme"
        Then the media "m-1" is in the folder "f-2"

    Scenario: The events log describes the events of the library
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "m-1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/folders/register"
        When I submit a form with "name:Contratos" and button "Crear"
        When I go to "/es/library/media/edit/m-1"
        When I submit a form with "name:Logotipo corporativo" and button "Guardar y salir"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a text "Creada la carpeta Contratos"
        Then I see a text "Actualizados los datos del archivo Logotipo corporativo"
