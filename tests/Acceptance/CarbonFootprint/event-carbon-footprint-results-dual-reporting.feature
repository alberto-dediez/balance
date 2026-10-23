Feature:
    I want the results step of an event carbon footprint to report the electricity by the market-based and the location-based methods

    Scenario: The results step shows the electricity by both methods
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given property "Recinto" with id "1111" of account "666" exists
        Given property "1111" is in country "C-ES"
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2026" exists
        Given electricity mix for country "C-ES" with id "MIX1" and emission factor "0.4" for year "2026" exists
        Given event carbon footprint "999" has an electricity activity using marketer "EM1" of 1000 kWh in assembly for country "C-ES"
        Given event carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "Electricidad · método de localización (kg CO₂eq)"
        Then I see a text "Total · método de localización (kg CO₂eq)"
        Then I see a text "1.500,00"
        Then I see a text "400,00"
        Then I do not see a text "sin factor de mix eléctrico"

    Scenario: The results step hides the electricity rows without electricity activities
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I do not see a text "Electricidad · método de mercado"

    Scenario: The results step warns about the electricity without an electricity mix
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2026" exists
        Given event carbon footprint "999" has an electricity activity using marketer "EM1" of 1000 kWh in assembly
        Given event carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "Actividades de electricidad sin factor de mix eléctrico para su país y año: 1."
