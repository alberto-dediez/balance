Feature:
    I want to remove the carbon footprint of a property

    Scenario: Removing a footprint empties the list and allows registering the same year again
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/remove" with csrf token "remove"
        Then I follow redirect
        Then the response status should be 200
        Then I see a table with 2 rows
        When I go to "/es/property-carbon-footprints/register"
        When I submit a form with "propertyId:1111|year:2025" and button "Registrar"
        Then I follow redirect
        Then I see a text "Cálculo de Huella de Carbono creado correctamente"

    Scenario: An admin removes a property that has a carbon footprint
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/remove/1111" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la propiedad"
