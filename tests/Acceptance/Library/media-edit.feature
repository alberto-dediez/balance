Feature:
    Ability to edit the details of a file of the media library

    Scenario: As an admin I can access the edit form
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/edit/1111"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can edit the details of a file
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/edit/1111"
        When I submit a form with "name:Logotipo corporativo|alternativeText:Logotipo" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Logotipo corporativo"

    Scenario: An unknown file cannot be edited
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/edit/9999"
        Then the response status should be 404

    Scenario: I can change the visibility of a file
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/edit/1111"
        Then I see a text "Visibilidad"
        When I submit a form with "name:logo.png|visibility:Public" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        When I go to "/es/library/media/edit/1111"
        Then I see a text "Enlace público"

    Scenario: A regular user also decides the visibility of a file
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/edit/1111"
        Then the response status should be 200
        Then I see a text "Visibilidad"
