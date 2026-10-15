Feature:
    I want to make sure that the system is allow to access the details of a purchase service

    Scenario: As an admin, I can access to edit purchase services
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given purchase service "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/purchase-services/edit/333"
        When I submit a form with "name:A new name|category:marketing|unit:EUR" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Bien o servicio comprado actualizado con éxito"

    Scenario: As an admin, I see the emission factors table when editing a purchase service
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given purchase service "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/purchase-services/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I do not see a text "Reciclado"

    Scenario: As an owner, I can't access to edit purchase services
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given purchase service "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/purchase-services/edit/333"
        Then the response status should be 403
