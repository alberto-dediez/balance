Feature:
    Ability for an owner to compensate the emissions of their account from the private area

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given event "Congreso" with id "888" of account "666" exists
        Given event carbon footprint with id "999" for event "888" of account "666" completed exists

    Scenario: The owner compensates the footprint of their event with their own credit and nothing is charged
        Given project "Reforestación" with id "1111" and price per tonne "20" and cost per tonne "15" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        When I am logged as "owner@dediez.software"
        When I go to "/es/compensate/event_carbon_footprint/888"
        Then the response status should be 200
        Then I see a text "Compensar con tus créditos"
        Then I see a text "Reforestación"
        When I click on link "Compensar"
        Then the response status should be 200
        Then I see a text "Compensas con tus propios lotes de crédito"
        Then there should be 0 contributions
        When I submit a form with "[co2eqKg]:2000|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Equivale a 2,000 t"
        When I submit a form with "[co2eqKg]:2000|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Compensar"
        Then I am redirected to route "owner_contribution_confirmation"
        Then the response status should be 200
        Then I see a text "Compensación registrada"
        Then I see a text "Sin cobro"
        Then the only contribution should be "paid"
        Then account "666" should have "8" available tonnes in project "1111"

    Scenario: The valuation is refreshed on its own without reloading the page
        Given project "Reforestación" with id "1111" and price per tonne "20" and cost per tonne "15" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        When I am logged as "owner@dediez.software"
        When I go to "/es/compensate/event_carbon_footprint/888"
        When I click on link "Compensar"
        When I submit a form with "[co2eqKg]:2000|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Recalcular" as an ajax request
        Then the response status should be 200
        Then I see a text "Equivale a 2,000 t"
        Then I see a text "Compensar 2,000 t"
        Then I do not see a text "Datos del certificado"
        Then there should be 0 contributions

    Scenario: The owner cannot compensate more than their own available credit
        Given project "Reforestación" with id "1111" and price per tonne "20" and cost per tonne "15" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "1" tonnes exists
        Given credit purchase "2222" is validated with "1" tonnes and reference "REF-1" paid by "paid_by_client"
        When I am logged as "owner@dediez.software"
        When I go to "/es/compensate/event_carbon_footprint/888"
        When I click on link "Compensar"
        When I submit a form with "[co2eqKg]:5000|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "No tienes crédito propio suficiente"
        Then there should be 0 contributions

    Scenario: Without own lots the owner pays like a guest
        Given social project "Escuela rural" with id "3333" and price per tonne "20" exists
        Given project "3333" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/compensate/event_carbon_footprint/888"
        Then the response status should be 200
        Then I see a text "Escuela rural"
        Then I do not see a text "Compensar con tus créditos"
        When I click on link "Aportar"
        Then the response status should be 200
        When I submit a form with "mode:free|amount:50|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Desglose"
        When I submit a form with "mode:free|amount:50|name:Hoteles del Sur SL|email:owner@example.com|country:ES" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should be "pending"

    Scenario: An owner of another account cannot compensate for this event
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/compensate/event_carbon_footprint/888"
        Then the response status should be 403
