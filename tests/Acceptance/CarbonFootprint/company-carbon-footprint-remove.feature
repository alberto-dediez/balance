Feature:
    I want to remove the carbon footprint of a company

    Scenario: Removing a footprint empties the list and allows registering the same year again
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/remove" with csrf token "remove"
        Then I follow redirect
        Then the response status should be 200
        Then I see a table with 2 rows
        When I go to "/es/company-carbon-footprints/register"
        When I submit a form with "companyId:C1|year:2025" and button "Registrar"
        Then I follow redirect
        Then I see a text "Cálculo de Huella de Carbono creado correctamente"

    Scenario: An admin removes a company that has a carbon footprint
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies/remove/C1" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la empresa"
