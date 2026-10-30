Feature:
    I want the franchisee or lessee of a property to contribute its scope 1 and 2 emissions through a public link

    Background:
        Given account "MyCompany" with id "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel Franquicia" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property "2222" has operating regime "franchised"
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given operator contribution with id "OC1" for company carbon footprint "999" and property "2222" of account "666" with email "operador@hotel.es" and token "tok-oc1" exists

    Scenario: The link redirects to the general step and shows the footprint
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1"
        Then I am redirected to route "public_operator_contribution_general"
        Then the response status should be 200
        Then I see a text "Hotelera"
        Then I see a text "Hotel Franquicia"
        Then I see a text "2025"

    Scenario: The general step requires the consent
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/general"
        When I submit a form with "contactName:Ana" and button "Siguiente"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."

    Scenario: Accepting the consent moves on to the data step with the consumption tables
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/general"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "contactName:Ana" and button "Siguiente"
        Then I am redirected to route "public_operator_contribution_data"
        Then the response status should be 200
        Then I see a text "Instalaciones"
        Then I do not see a text "Concepto"
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1"
        Then I am redirected to route "public_operator_contribution_data"

    Scenario: Sending the consumptions marks the contribution as answered
        Given operator contribution "OC1" has consent
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/data"
        When I submit the operator data form with a combustible "CB1" of "500" "kg"
        Then I am redirected to route "public_operator_contribution_answered"
        Then the response status should be 200
        Then I see a text "Gracias por aportar tus datos"
        Then operator contribution "OC1" should have status "answered"
        Then operator contribution "OC1" should have 1 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: With the precalculated mark the franchisee reports its emissions
        Given operator contribution "OC1" has consent
        Given operator contribution "OC1" is precalculated
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/data"
        Then I see a text "Concepto"
        When I submit the operator data form with reported emissions "Caldera" of "2" "t" for source "fixed_facilities"
        Then I am redirected to route "public_operator_contribution_answered"
        Then operator contribution "OC1" should have status "answered"
        Then operator contribution "OC1" should have 1 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: Changing the precalculated mark after answering removes the sent data
        Given operator contribution "OC1" has consent
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/general"
        When I tick the checkbox "consent_accepted"
        When I tick the checkbox "general[precalculated]"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_operator_contribution_data"
        Then operator contribution "OC1" should have 0 activities of source "fixed_facilities" in scope "scope_1"
        Then operator contribution "OC1" should have status "pending"

    Scenario: The answered screen without having answered redirects to the general step
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/answered"
        Then I am redirected to route "public_operator_contribution"
        Then I am redirected to route "public_operator_contribution_general"
        Then I do not see a text "Gracias por aportar tus datos"

    Scenario: An unknown token is not found
        When I go to "/es/carbon-footprint/operator-contribution/unknown"
        Then the response status should be 404

    Scenario: An ignored contribution is not found
        Given operator contribution "OC1" is ignored
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1"
        Then the response status should be 404

    Scenario: A completed footprint closes the link
        Given company carbon footprint "999" is completed
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1"
        Then the response status should be 409

    Scenario: After answering the franchisee can modify the data
        Given operator contribution "OC1" has consent
        Given operator contribution "OC1" is answered with a combustible "CB1" activity of "1000" kg
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/answered"
        Then I see a text "Modificar mis datos"
        When I go to "/es/carbon-footprint/operator-contribution/tok-oc1/data"
        When I submit the operator data form with a combustible "CB1" of "500" "kg"
        Then I am redirected to route "public_operator_contribution_answered"
        Then operator contribution "OC1" should have 1 activities of source "fixed_facilities" in scope "scope_1"
