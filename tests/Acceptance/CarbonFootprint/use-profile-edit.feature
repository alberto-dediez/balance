Feature:
    I want to make sure that the system is allow to access the details of a use profile

    Scenario: As an admin, I can access to edit use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given use profile "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/edit/333"
        When I submit a form with "name:A new name|unit:ud" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Perfil de uso actualizado con éxito"

    Scenario: As an admin, I can change the unit of a use profile to square meters
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given use profile "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/edit/333"
        When I submit a form with "name:Item 1|unit:m2" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Perfil de uso actualizado con éxito"
        When I go to "/es/admin/use-profiles"
        Then I see a text "Metro cuadrado"

    Scenario: As an admin, I see the emission factors table when editing a use profile
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given use profile "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I do not see a text "Reciclado"

    Scenario: As an owner, I can't access to edit use profiles
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given use profile "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/use-profiles/edit/333"
        Then the response status should be 403
