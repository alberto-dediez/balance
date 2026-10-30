Feature:
    I want to invite the franchisee or lessee of a non-consolidated property to contribute its scope 1 and 2 emissions

    Background:
        Given user "system" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel Propio" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel Franquicia" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property "2222" has operating regime "franchised"
        Given property "Hotel Arrendado" with id "3333" of account "666" exists
        Given property "3333" has company "C1" assigned
        Given property "3333" has operating regime "leased_out"
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists

    Scenario: A franchise without invitation offers to invite its franchisee and shows the contact of the property
        Given property "2222" has operator contact "ficha@hotel.es"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "Franquiciado / arrendatario"
        Then I see a text "ficha@hotel.es"
        Then I see a link to "/es/company-carbon-footprints/999/operators/2222/invite"

    Scenario: Inviting the franchisee sends the email and saves the contact in the property
        Given property "2222" has operator contact "ficha@hotel.es"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/2222/invite"
        Then the response status should be 200
        Then I see a form field "contactEmail" with value "ficha@hotel.es"
        When I submit a form with "contactEmail:operador@hotel.es|contactName:Ana" and button "Enviar invitación"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then an email with subject "Aporta los consumos de 2025 de Hotel Franquicia para la huella de Hotelera" was sent to "operador@hotel.es"
        Then there should be 1 active operator contributions for company carbon footprint "999"
        Then property "2222" should have operator contact "operador@hotel.es"
        Then I see a text "Invitación enviada a operador@hotel.es"
        Then I see a text "operador@hotel.es"
        Then I see a text "Pendiente"

    Scenario: An email accepted by the browser but not by the domain shows a field error
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/2222/invite"
        When I submit a form with "contactEmail:.ana@hotel.es" and button "Enviar invitación"
        Then the response status should be 200
        Then I see a text "El email no es válido."
        Then there should be 0 active operator contributions for company carbon footprint "999"

    Scenario: A property cannot be invited twice in the same footprint
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/2222/invite"
        When I submit a form with "contactEmail:otro@hotel.es" and button "Enviar invitación"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then I see a text "Esta propiedad ya tiene una invitación activa"
        Then there should be 1 active operator contributions for company carbon footprint "999"

    Scenario: An answered contribution is the origin of the franchise row
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then I see a text "Aportación del franquiciado o arrendatario"
        Then I see a text "1.000,00"
        Then I see a text "Contestado"
        When I submit the company scope 3 step form with manual emissions "4500" for property "3333"
        Then company carbon footprint "999" should have "1000" kg in source "franchises"

    Scenario: A completed property footprint prevails over the contribution
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then I see a text "Huella 2025 completada"
        When I submit the company scope 3 step form with manual emissions "4500" for property "3333"
        Then company carbon footprint "999" should have "3000" kg in source "franchises"

    Scenario: Ignoring the contribution leaves the row without data and unignoring brings it back
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I am logged as "owner@dediez.software"
        When I go to the "ignore" action of operator contribution "OC1" of company carbon footprint "999"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then operator contribution "OC1" should have status "ignored"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then I see a text "Sin datos"
        When I submit the company scope 3 step form with manual emissions "4500" for property "3333"
        Then company carbon footprint "999" should have "0" kg in source "franchises"
        When I go to the "unignore" action of operator contribution "OC1" of company carbon footprint "999"
        Then operator contribution "OC1" should have status "answered"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "4500" for property "3333"
        Then company carbon footprint "999" should have "1000" kg in source "franchises"

    Scenario: Removing the invitation lets the owner invite again
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/OC1/remove" with csrf token "remove"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then operator contribution "OC1" should be removed
        Then there should be 0 active operator contributions for company carbon footprint "999"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then I see a link to "/es/company-carbon-footprints/999/operators/2222/invite"

    Scenario: Resending the invitation sends the email again
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        When I am logged as "owner@dediez.software"
        When I go to the "resend" action of operator contribution "OC1" of company carbon footprint "999"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then an email with subject "Aporta los consumos de 2025 de Hotel Franquicia para la huella de Hotelera" was sent to "operador@hotel.es"

    Scenario: The owner sees the answers of the franchisee
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/OC1"
        Then the response status should be 200
        Then I see a text "operador@hotel.es"
        Then I see a text "Por consumos"
        Then I see a text "1.000,00"

    Scenario: An admin cannot invite the franchisee
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/company-carbon-footprints/999/operators/2222/invite"
        Then the response status should be 403
