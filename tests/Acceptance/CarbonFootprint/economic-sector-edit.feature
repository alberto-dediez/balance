Feature:
    I want to make sure that the system is allow to access the details of a economic sector

    Scenario: As an admin, I can access to edit economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/edit/333"
        When I submit a form with "name:A new name|unit:EUR" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Sector económico actualizado con éxito"

    Scenario: As an admin, I can change the currency of a economic sector to US dollar
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/edit/333"
        When I submit a form with "name:Item 1|unit:USD" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Sector económico actualizado con éxito"
        When I go to "/es/admin/economic-sectors"
        Then I see a text "Dólar estadounidense"

    Scenario: As an admin, I can't set the classification code of another economic sector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Item 1" with id "333" and classification code "25" exists
        Given economic sector "Item 2" with id "444" and classification code "26" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/edit/444"
        When I submit a form with "name:Item 2|classificationCode:25|unit:EUR" and button "Guardar y salir"
        Then the response status should be 200
        Then I see a text "Ya existe un sector económico con ese código"

    Scenario: As an admin, I see the emission factors table when editing a economic sector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I do not see a text "Reciclado"

    Scenario: As an owner, I can't access to edit economic sectors
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given economic sector "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/economic-sectors/edit/333"
        Then the response status should be 403
