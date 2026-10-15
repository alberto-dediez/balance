Feature:
    I want to make sure that the system is allow to access the details of a capital good

    Scenario: As an admin, I can access to edit capital goods
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given capital good "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/edit/333"
        When I submit a form with "name:A new name|category:furniture_and_equipment|unit:EUR" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Bien de capital actualizado con éxito"

    Scenario: As an admin, I can change the unit of a capital good to square meters
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given capital good "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/edit/333"
        When I submit a form with "name:Item 1|category:renovations|unit:m2" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Bien de capital actualizado con éxito"
        When I go to "/es/admin/capital-goods"
        Then I see a text "Metro cuadrado"

    Scenario: As an admin, I see the emission factors table when editing a capital good
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given capital good "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I do not see a text "Reciclado"

    Scenario: As an owner, I can't access to edit capital goods
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given capital good "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/capital-goods/edit/333"
        Then the response status should be 403
