<?php

namespace N98\Magento\Command\Eav\Attribute\Create;

class DummyValues
{
    private $faker;

    private $sizes = array(
        'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
        '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60',
    );

    private $designer = array(
        '08Sircus', '11 By Boris Bidjan Saberi', '1-100', '3.1 Phillip Lim', '32 Paradis Sprung Frères', '321', '3X1',
        '5 Preview', '69', '7 For All Mankind', '8pm', 'S Max Mara', 'A Diciannoveventitre', 'A.F.Vandevorst', 'A.L.C.',
        'A.P.C.', 'Aalto', 'Ack', 'Adam Lippes', 'Adidas', 'Adidas By Raf Simons', 'Adidas By Stella Mccartney',
        'Adidas Originals', 'Adieu Paris', 'Ag Jeans', 'Agnona', 'Ahlem', 'Akris', 'Alaïa Vintage', 'Alberta Ferretti',
        'Alberta Ferretti Vintage', 'Alberto Biani', 'Alberto Fasciani', 'Alberto Fermani', 'Aleksandr Manamïs',
        'Alessandra Marchi', 'Alex Perry', 'Alexa Wagner', 'Alexander McQueen', 'Alexander Wang', 'Alexandre Birman',
        'Alexandre Vauthier', 'Alexis Bittar', 'Alexis Mabille', 'Alfred Leder', 'Algenist', 'Alice Archer',
        'Alice Waese', 'Alice+Olivia', 'Alinka', 'Alison Lou', 'Alix', 'Allison Bryan', 'Allude', 'Ally Capellino',
        'Altruis By Vinaya', 'Altuzarra', 'Alvaro Gonzalez', 'Alyx', 'Amapô', 'Ambush', 'Amedeo', 'Amélie Pichard',
        'Amen', 'Ami Alexandre Mattiussi', 'Amo', 'A-Morir', 'Anapsara', 'Ancient Greek Sandals', 'Andrea Marques',
        'Andrea Ya aqov', 'Andy Wolf Eyewear', 'Anine Bing', 'Anita Ko', 'Ann Demeulemeester',
        'Ann Demeulemeester Blanche', 'Anna Baiguera', 'Anna Karin Karlsson', 'Anna Kosturova', 'Anndra Neen',
        'Anne Semonin', 'Anne Sofie Madsen', 'Annelise Michelson', 'Anrealage', 'Anthony Vaccarello', 'Antonia Zander',
        'Antonio Berardi', 'Antonio Marras', 'Anya Hindmarch', 'Aperlai', 'Apiece Apart',
        'April Chrichton & Nicholas Party', 'Aquazzura', 'Area Di Barbara Bologna', 'Arielle De Pinto',
        'Aristide Najean', 'Armani Collezioni', 'Armani Jeans', 'Army Yves Salomon', 'As29', 'Ash', 'Asherali Knopfer',
        'Ashish', 'Ashley Pittman', 'Aspesi', 'Assin', 'Assouline', 'Astley Clarke', 'Astraet', 'Atelier Bâba',
        'Atelier Buffile', 'Atelier Paulin', 'Athena', 'Atlantic Stars', 'Atlantique Ascoli',
        'Atm Anthony Thomas Melillo', 'Aurelia Probiotic Skincare', 'Aurelie Bidermann', 'Ava Adore', 'Avant Toi',
        'Avelon', 'Awake', 'B May', 'Badgley Mischka', 'Baja East', 'Bakel', 'Balenciaga Eyewear', 'Balenciaga Vintage',
        'Ballin', 'Bally', 'Balmain', 'Bao Bao Issey Miyake', 'Barbara Bui', 'Barbara Casasola', 'Barbara I Gongini',
        'Bark', 'Barn\'s', 'Barrie', 'Bassike', 'Bata', 'Bazar Deluxe', 'Bea Bongiasca', 'Beau Souci', 'Becca',
        'Beek..', 'Bella Freud', 'Belstaff', 'Bernhard Willhelm', 'Beth Orduna', 'Bhindi Jewelers', 'Bianca Spender',
        'Biba Vintage', 'Bibi Van Der Velden', 'Bintthani', 'Bionda Castana', 'Birkenstock', 'Bjorg', 'Blancha',
        'Blk Dnm', 'Blugirl', 'Blumarine', 'Boboutic', 'Borbonese', 'Bottega Veneta Eyewear', 'Boutique Moschino',
        'Boys Forever', 'Brandon Maxwell', 'Brian Atwood', 'Brioni', 'Brock Collection', 'Brother Vellies',
        'Brunello Cucinelli', 'Brute', 'Building Block', 'Bukkehave', 'Bulgari', 'Burberry', 'Burberry Brit',
        'Burberry Kids', 'Burberry London', 'Burberry Prorsum', 'Burberry Vintage', 'Buscemi', 'Buttero', 'By Boe',
        'By Kilian', 'By Malene Birger', 'By Terry', 'By Walid', 'Byredo', 'Ca&Lou', 'Calleen Cordero', 'Callens',
        'Calvin Klein Collection', 'Calvin Klein Jeans', 'Camilla', 'Camilla And Marc', 'Canali', 'Cane + Austin',
        'Capucci', 'Car Shoe', 'Carhartt', 'Carin Wester', 'Carl Rotter', 'Carole Shashona', 'Carolina Bucci',
        'Carolina Herrera', 'Cartier Vintage', 'Carven', 'Casadei', 'Case Scenario', 'Casey Casey', 'Casio',
        'Castañer', 'Catherine Michiels', 'Cazal', 'Cecilia Prado', 'Cecilie Copenhagen', 'Cédric Charlier',
        'Céline Eyewear', 'Céline Vintage', 'Cesare Paciotti', 'Chalayan', 'Chanel Eyewear', 'Chanel Vintage',
        'Chantecaille', 'Charlet Par Aime', 'Charlotte Chesnais', 'Charlotte Olympia', 'Cherevichkiotvichki',
        'Chiara Ferragni', 'Chie Mihara', 'Chinti And Parker', 'Chloé', 'Chloe Gosselin', 'Christian Dior',
        'Christian Dior Vintage', 'Christian Koban', 'Christian Lacroix Vintage', 'Christian Siriano',
        'Christian Wijnants', 'Christina Debs', 'Christopher Esber', 'Christopher Kane', 'Cinq A Sept', 'Cinzia Araia',
        'Cire Trudon', 'Citizens Of Humanity', 'Cityshop', 'Claire Barrow', 'Clarice Price Thomas',
        'Claude Montana Vintage', 'Claustrum', 'Closed', 'Co', 'Coach', 'Coco And Breezy', 'Colbert Md', 'Coliac',
        'Color Wow', 'Comme Des Garçons', 'Comme Des Garçons Comme Des Garçons', 'Comme Des Garçons Girl',
        'Comme Des Garçons Noir Kei Ninomiya', 'Comme Des Garçons Play', 'Comme Des Garçons Tricot',
        'Comme Des Garçons Vintage', 'Comme Des Garçons Wallet', 'Common Projects', 'Converse', 'Corlette',
        'Cornelia Webb', 'Côte&Ciel', 'Courrèges', 'Courrèges Vintage', 'Cover Fx', 'Creatures Of The Wind',
        'Cristina Ortiz', 'Current/Elliott', 'Cushnie Et Ochs', 'Cutler & Gross', 'Cutuli Cult', 'Cvc Stones', 'Dagmar',
        'Damir Doma', 'Dana Kellin', 'Dana Rebecca', 'Daniela Gregis', 'Daniela Villegas', 'David Koma',
        'Delfina Delettrez', 'Delpozo', 'Derek Lam', 'Derek Lam 10 Crosby', 'Desa Collection', 'Designinverso', 'Dezso',
        'Diadora', 'Diane Von Furstenberg', 'Diesel', 'Diesel Black Gold', 'Dima', 'Dimissianos & Miller', 'Dion Lee',
        'Dionea Orcini', 'Dior Eyewear', 'Diptyque', 'Dita Eyewear', 'DKNY', 'DKNY Pure', 'Dodo Bar Or',
        'Dolce & Gabbana', 'Dominic Jones', 'Dondup', 'Dorateymur', 'Doreen Mellen', 'Dorothee Schumacher', 'Dosa',
        'Dovetusai', 'Dr Sebagh', 'Dr. Martens', 'DressCamp', 'Dries Van Noten Eyewear', 'Drome', 'Dsquared2',
        'Dusan', 'Duvetica', 'Dylanlex', 'Each X Other', 'Eddie Borgo', 'Edge Of Ember', 'Edie Parker', 'Edun', 'Eggs',
        'Egrey', 'Ejxiii', 'Ek Thongprasert', 'Elena Ghisellini', 'Eleventy', 'Elie Saab', 'Elie Tahari', 'Elise Dray',
        'Elizabeth And James', 'Ellen Conde', 'Ellery', 'Emanuel Ungaro', 'Emanuel Ungaro Vintage', 'Emanuele Bicocchi',
        'Emilio Pucci', 'Emilio Pucci Vintage', 'Emma Hardie', 'Emporio Armani', 'Emporio Armani Vintage',
        'Enfants Riches Deprimes', 'Engineered Garments', 'Epos', 'Equipment', 'Erdem', 'Erickson Beamon',
        'Erika Cavallini', 'Ermanno Gallamini', 'Ermanno Scervino', 'Escada Vintage', 'Eshvi', 'Espèces',
        'Esteban Cortazar', 'Être Cécile', 'Etro', 'Études Studio', 'Eugenia Kim', 'Eve Lom', 'Extreme Cashmere',
        'Eyevan7285', 'Eytys', 'Fabiana Filippi', 'Facetasm', 'Factory 900', 'Fad Three', 'Fadeless', 'Faith Connexion',
        'Faliero Sarti', 'Family Affair', 'Fannie Schiavoni', 'Fashion Clinic', 'Faustine Steinmetz', 'Fausto Puglisi',
        'Fay', 'Federica Moretti', 'Fendi', 'Fendi Vintage', 'Feverish', 'Figue', 'Filles A Papa', 'Filù Hats',
        'Fiorentini + Baker', 'Fiya', 'Fleur Du Mal', 'For Love And Lemons', 'For Restless Sleepers', 'Fornasetti',
        'Fornasetti Profumi', 'Forte Forte', 'Frame Denim', 'Frances Valentine', 'Francesco Demaria', 'Frank & Eileen',
        'Franz Marfurt Lucerne', 'Frency & Mercury', 'Frends', 'Furla', 'G.V.G.V.', 'Gabriela Hearst',
        'Gabriella Ingram', 'Galvan', 'Gareth Pugh', 'Garrard', 'Garrett Leight', 'Gas Bijoux', 'Gavello', 'Gaydamak',
        'Gcds', 'Gedebe', 'Gemco', 'Geoffrey B. Small', 'Getting Back To Square One', 'Giada Benincasa', 'Giamba',
        'Giambattista Valli', 'Gianfranco Ferre Vintage', 'Gianluca Capannolo', 'Gianvito Rossi', 'Gig',
        'Gigi Burris Millinery', 'Gigli Vintage', 'GINGER & SMART', 'Giorgio Armani', 'Giorgio Armani Vintage',
        'Giorgio Brato', 'Gisele For Eshvi', 'Giuliana Romanno', 'Giuseppe Zanotti Design', 'Givenchy',
        'Givenchy Vintage', 'Gold And Wood', 'Golden Goose Deluxe Brand', 'Goldfaden Md', 'Goldsign',
        'Good For Nothing Emb', 'Goti', 'Goyard Vintage', 'Greg Lauren', 'Grenson', 'Greta Constantine', 'Grey Ant',
        'Grey Jason Wu', 'G-Star', 'Gucci Eyewear', 'Guidi', 'Guild Prime', 'Guy Laroche Vintage', 'Hache',
        'Haider Ackermann', 'Hakusan', 'Halston Heritage', 'Hanna Wallmark', 'Harcoza', 'Harris Wharf London', 'Haus',
        'Hayward', 'Hector Magazine', 'Heidi Klein', 'Heikki Salonen', 'Helmut Lang', 'Helmut Lang Vintage',
        'Hender Scheme', 'Henderson Baracco', 'Henrik Vibskov', 'Henson', 'Hermès Vintage', 'Herno', 'Heroine Sport',
        'Herrlicht', 'Hervé Léger', 'Hipanema', 'Hirondelle', 'Hl Heddie Lovu', 'Hogan', 'Hogan Rebel', 'Holiday',
        'Hood By Air', 'Hoorsenbuhs', 'Hope', 'Horisaki Design & Handel', 'House Of Holland', 'House Of Voltaire',
        'Htc Hollywood Trading Company', 'Hudson', 'Humanoid', 'Hussein Bazaza', 'Iceberg', 'Idonthaveasister',
        'Ilana Ariel', 'Ilaria Nistri', 'Ileana Makri', 'Illesteva', 'Iluminage', 'Imogen Belfield', 'Incotex',
        'Individual Sentiments', 'Industrie Veneziane', 'Ingie Paris', 'Institut Esthederm', 'Inverni',
        'Io Ivana Omazic', 'Iosselliani', 'Irene Neuwirth', 'Iro', 'Isaac Reina', 'Isaac Sellam Experience',
        'Isabel Benenato', 'Isabel Marant', 'Isabel Marant Étoile', 'Isabel Marant For Oliver Peoples', 'Isolda',
        'Issey Miyake', 'Issey Miyake Cauliflower', 'Issey Miyake Men', 'Issey Miyake Vintage', 'Italia Independent',
        'Ivo Scunzani', 'Ivy & Liv', 'Ivy Kirzhner', 'J Brand', 'J.W. Anderson', 'Jacob Cohen', 'Jacquemus',
        'Jacques Marie Mage', 'Jacquie Aiche', 'Jagga', 'James Perse', 'James Read', 'Jamin Puech', 'Jane Bowler',
        'Janis Savitt', 'Japsis Jewellery', 'Jason Wu', 'Jay Ahr', 'Jc De Castelbajac Vintage',
        'Jean Louis Scherrer Vintage', 'Jean Paul Gaultier Vintage', 'Jean-François Mimilla', 'Jean-Michel Cazabat',
        'Jeffrey Campbell', 'Jenni Kayne', 'Jeremy Deller', 'Jeremy Scott', 'Jérôme Dreyfuss', 'Jessie Harris',
        'Jezebel London', 'Jil Sander', 'Jil Sander Navy', 'Jil Sander Vintage', 'Jimmy Choo', 'Jitrois',
        'Joanna Laura Constantine', 'Jocelyn', 'Joëlle Jewellery', 'John Booth', 'John Brevard',
        'John Galliano Vintage', 'John Smedley', 'Joie', 'Jonathan Cohen', 'Jonathan Saunders', 'Jonathan Simkhai',
        'Joseph', 'Joshua Sanders', 'Jour/Né', 'Jozica', 'Juicy Couture', 'Julien David', 'Junior Gaultier',
        'Junya Watanabe Comme Des Garçons', 'Junya Watanabe Comme Des Garçons Vintage', 'Just Cavalli', 'Just Female',
        'Jvdf', 'K. Jacques', 'Kamushki', 'Kane Ny', 'Kansai Yamamoto Vintage', 'Karen Walker Eyewear', 'Karolin',
        'Kat Burki', 'Katherine Wallach', 'Katrine Kristensen', 'Kaufmanfranco', 'Kdia', 'Kenzo', 'Kenzo Vintage',
        'Kevyn Aucoin', 'Khai Khai', 'Khirma Eliazov', 'Kijima Takayuki', 'Kilometre X The Webster', 'Kim Mee Hye',
        'Kimberly Mcdonald', 'Kinz Kanaan', 'Kitx', 'Ko Studio', 'Koral', 'Krewe Du Optic', 'Kris Van Assche',
        'Kristensen Du Nord', 'Kristin Hanson', 'Krizia Vintage', 'Ksubi', 'KTZ', 'Kuboraum', 'Kyme', 'L.G.R',
        'La Brune Et La Blonde', 'La Mania', 'La Perla', 'Label Under Construction', 'Laboratorigarbo', 'Lafayette 148',
        'Lala Berlin', 'Lamat', 'Lancer', 'Lanvin', 'Lanvin Vintage', 'Lara Bohinc', 'Lareida', 'Larkspur & Hawk',
        'Larsson & Jennings', 'Laura B', 'Lauren Klassen', 'Laurence Dacade', 'Ld Tuttle',
        'Le Bon Marche X The Webster', 'Le Ciel Bleu', 'Le Coq Sportif', 'Le Kasha', 'Le Kilt', 'Le Specs',
        'Leather Crown', 'Lee Brennan Design', 'Leivankash', 'Lela Rose', 'Lemaire', 'Lemlem', 'Les Petits Joueurs',
        'Lesca', 'Levi\'s', 'Linda Farrow', 'Linda Farrow Gallery', 'Lindberg', 'Lipstick Queen',
        'Lisa Marie Fernandez', 'Liska', 'Liska Haute Fourrure By Romain Brau', 'Live The Process With Chelsea Leyland',
        'Living Proof', 'Lizzie Fortunato Jewels', 'Local Supply', 'Loeffler Randall', 'Loewe', 'Loquet',
        'Loree Rodkin', 'Loren Stewart', 'Loro Piana', 'Lost & Found Ria Dunn', 'Lost & Found Rooms', 'Lotos',
        'Louis Feraud Vintage', 'Louis Vuitton Vintage', 'Love Moschino', 'Loveless', 'Loyd/Ford', 'Lucas Hugh',
        'Lucien Pellat Finet', 'Luis Miguel Howard', 'Luis Morais', 'Luisa Cevese Riedizioni', 'Lulu Guinness',
        'Luxury Fashion', 'Lydia Courteille', 'M Missoni', 'M Missoni Vintage', 'M2malletier', 'Ma+', 'Mad Et Len',
        'Madame', 'Magda Butrym', 'Maharishi', 'Maison Close', 'Maison Kitsuné', 'Maison Margiela',
        'Maison Margiela Vintage', 'Maison Michel', 'Maison Rabih Kayrouz', 'Maison Recuerdo', 'Maison Ullens',
        'Maiyet', 'Majestic Filatures', 'Majesty Black', 'Malia Mills', 'Malin+Goetz', 'Malone Souliers', 'Mame',
        'Manebi', 'Manning Cartell', 'Manoush', 'Mansur Gavriel', 'Manu Atelier', 'Mara Hoffman', 'Marc Alary',
        'Marc By Marc Jacobs', 'Marc Jacobs', 'Marc Le Bihan', 'Marcelo Burlon County Of Milan', 'March La.B',
        'Marchesa', 'Marchesa Notte', 'Marco De Vincenzo', 'Maria Black', 'Maria Lucia Hohan', 'Maria Teresa Sottile',
        'Marie Helene De Taillac', 'Mark Cross', 'Markus Lupfer', 'Marni', 'Marsèll', 'Marshall', 'Martha Medeiros',
        'Martine Jarlgaard', 'Mary Katrantzou', 'Mary Katrantzou X Adidas Originals', 'Maryam Nassir Zadeh',
        'Marysia', 'Masnada', 'Massimo Alba', 'Master & Dynamic', 'Masunaga', 'Matar', 'Mathilde Danglade',
        'Maticevski', 'Matsuda', 'Matthew Darbyshire', 'Matthew Williamson', 'Mawi', 'Max Mara', 'Max Mara Studio',
        'Max Tan', 'Maxime Llorens', 'Maxwell Snow', 'MCM', 'Mcm Vintage', 'McQ Alexander McQueen', 'Me&Mr.Gentleman',
        'Melissa + Jeremy Scott', 'Melissa Joy Manning', 'Melissa Odabash', 'Mes Demoiselles', 'Miahatami', 'Miansai',
        'Michael Kors', 'Michael Michael Kors', 'Michael Schmidt', 'Michel Vivien', 'Micol Ragni', 'Mih Jeans',
        'Mihara Yasuhiro', 'Milly', 'Minimarket', 'Minjukim', 'Minna Parikka', 'Mira Mikati', 'Missoni',
        'Missoni Vintage', 'Miu Miu Eyewear', 'Mm6 Maison Margiela', 'Moa Master Of Arts', 'Moeva', 'Monan',
        'Moncler', 'Moncler Gamme Rouge', 'Moncler Grenoble', 'Moncler X Erdem', 'Monday Active', 'Monica Sordo',
        'Monies', 'Monique Lhuillier', 'Monocle Eyewear', 'Monreal London', 'Moo Piyasombatkul', 'Moohong',
        'Moratorium', 'Moreau', 'Moschino', 'Moschino Vintage', 'Mother', 'Mother Of Pearl', 'Mou', 'Movitra',
        'Mr & Mrs Italy', 'MSGM', 'Mua Mua', 'Mugler', 'Murmur', 'Musée', 'Muubaa', 'Muveil', 'Mykita',
        'Myriam Schaefer', 'Myswear', 'N.Peal', 'Nafsika Skourti', 'Narciso Rodriguez', 'Nasir Mazhar',
        'Natasha Collis', 'Natasha Zinko', 'Nathalie Trad', 'Natura Bisse', 'Neil Barrett', 'Nektar De Stagni',
        'Neuw', 'Nevermind', 'New Balance', 'Newbark', 'Nicholas Kirkwood', 'Nicole Miller', 'Nicopanda',
        'Night Market', 'Nike', 'Nikos Koulis', 'Nili Lotan', 'Nina Ricci', 'Nina Ricci Vintage', 'Nixon', 'Niza Huang',
        'Nº21', 'Nobody Denim', 'Nomia', 'Noor Fares', 'Noritamy', 'Norma Kamali', 'Norse Projects', 'NSF', 'Nuface',
        'O&M', 'Oamc', 'Obey', 'Occulter', 'Odeeh', 'Odeur', 'Of Rare Origin', 'Officine Creative', 'Off-White',
        'Oliver Goldsmith', 'Oliver Peoples', 'Olympia Le-Tan', 'One Oak By Sara', 'Onia', 'Opening Ceremony',
        'Organic By John Patrick', 'Orolog By Jaime Hayon', 'Oscar de la Renta', 'Oskia', 'Osman', 'Ouigal',
        'P.A.R.O.S.H.', 'Paco Rabanne', 'Paige', 'Palm Angels', 'Paloma Barceló', 'Pamela Love', 'Paname',
        'Pantos Paris', 'Paolo Piovan', 'Parfums De Marly', 'Pascia', 'Paul & Joe', 'Paul Andrew', 'Paul Smith',
        'Paul Smith Black Label', 'Paula Cademartori', 'Paule Ka', 'Pb 0110', 'Pearl', 'Pearls Before Swine',
        'Pedro Garcia', 'Pelle Moda', 'Pepa Pombo', 'Peppercotton', 'Percy And Reed', 'Perricone Md', 'Perrin Paris',
        'Persol', 'Persol Vintage', 'Petar Petrov', 'Peter & May Walk', 'Peter Cohen', 'Peter Jensen', 'Peter Non',
        'Peter Pilotto', 'Petit Bateau', 'Petralux', 'Phat Buddha', 'Philipp Plein', 'Philippe Model',
        'Philosophy Di Lorenzo Serafini', 'Piamita', 'Piazza Sempione', 'Pierre Balmain', 'Pierre Cardin Vintage',
        'Pierre Hardy', 'Pierre-Louis Mascia', 'Piers Atkinson', 'Pleats Please By Issey Miyake',
        'Pleats Please By Issey Miyake Vintage', 'Plein Sud', 'Pollini', 'Polo Ralph Lauren', 'Pomellato',
        'Porter-Yoshida & Co', 'Ports 1961', 'Poupette St Barth', 'Prabal Gurung', 'Prada Eyewear', 'Prada Vintage',
        'Pramma', 'Preen By Thornton Bregazzi', 'Premiata', 'Premiata White', 'Prism', 'Proenza Schouler',
        'Protagonist', 'PS Paul Smith', 'Pt01', 'Puma', 'Puro Iosselliani', 'R13', 'Rachel Comey', 'Racil',
        'Radical Skincare', 'Rag & Bone', 'Rag & Bone /Jean', 'Ralph Lauren', 'Ralph Lauren Black',
        'Ralph Lauren Purple', 'Ram', 'Ramy Brook', 'Raquel Allegra', 'Rauwolf', 'Ray-Ban', 'Re/Done', 'Reality Studio',
        'Rebecca Minkoff', 'Rebecca Taylor', 'Rebecca Vallance', 'Red Valentino', 'Reebok', 'Reinaldo Lourenço',
        'Reinhard Plank', 'Ren', 'René Caovilla', 'Renee So', 'Repetto', 'Retrosuperfuture', 'Rewind Vintage Affairs',
        'Rick Owens', 'Rick Owens DRKSHDW', 'Rick Owens Lilies', 'Ritch Erani NYFC', 'Robert Clergerie',
        'Roberta Di Camerino Vintage', 'Roberto Cavalli', 'Roberto Collina', 'Roberto Del Carlo', 'Roberto Marroni',
        'Rochas', 'Rochelle Sara', 'Rocio', 'Rockins', 'Rodarte', 'Rodebjer', 'Rodial', 'Roksanda', 'Roland Mouret',
        'Romeo Gigli Vintage', 'Ros Millar', 'Rosa De La Cruz', 'Rosa Maria', 'Rosamosario', 'Rosantica',
        'Rosetta Getty', 'Rosie Assoulin', 'Rovant Antiqu', 'Rta', 'Ruifier', 'Rula Galayini', 'Rundholz',
        'Rundholz Black Label', 'Rupert Sanderson', 'Ryan Lo', 'Ryan Roche', 'Sabine G', 'Sacai', 'Sachajuan',
        'Saint Laurent', 'Saks Potts', 'Salinas', 'Saloni', 'Salvatore Ferragamo', 'Salvatore Santoro', 'Sam & Lavi',
        'Sam Edelman', 'Sama Eyewear', 'Samantha Sung', 'Samira13', 'Samsung By Colombo Via Della Spiga', 'San Lorenzo',
        'Sandrine Rose', 'Sandy Liang', 'Santoni', 'Saqqara', 'Sara Battaglia', 'Sara Weinstock', 'Sarah & Sebastian',
        'Sarah Angold Studio', 'Sarah Chapman', 'Sarah Chofakian', 'Sarah Noor', 'Sarah’s Bag', 'Sartore', 'Saturnino',
        'Saucony', 'Savas', 'Scanlan Theodore', 'Schutz', 'Scunzani Ivo', 'Sea', 'See By Chloé', 'See Concept', 'Seeme',
        'Self-Portrait', 'Selin Kent', 'Sener Besim', 'Serapian', 'Sergio Rossi', 'Serpui',
        'Shades Of Grey By Micah Cohen', 'Shamballa Jewels', 'Shaun Leane', 'Shinola', 'Shiseido', 'Shourouk',
        'Shrimps', 'Shylee Rose', 'Si-Jay', 'Silvano Sassetti', 'Simon Harrison', 'Simon Miller', 'Simona Tagliaferri',
        'Simone Rocha', 'Sirciam', 'Sjal', 'Slave To Ancestors', 'Smoke X Mirrors', 'Smythe', 'Smythson',
        'Société Anonyme', 'Soha Sardinia', 'Soludos', 'Sonia By Sonia Rykiel', 'Sonia Rykiel', 'Sonia Rykiel Vintage',
        'Sophia Webster', 'Sophie Bille Brahe', 'Sophie Hulme', 'Sophie Theallet', 'Soulland', 'Spektre',
        'Spencer Vladimir', 'Spinelli Kilcollin', 'Stampd', 'Stefano Mortari', 'Steffen Schraut', 'Stella McCartney',
        'Stephan Schneider', 'Stephane Kélian', 'Stephen Sprouse Vintage', 'Stephen Webster', 'Stine Goya', 'Stone',
        'Stouls', 'Strut This', 'Stuart Weitzman', 'Studio Chofakian', 'Stussy', 'Stutterheim', 'Suicoke',
        'Sun Buddies', 'Sunday Riley', 'Suno', 'Superga', 'Suzusan', 'SW/TCH', 'Swear', 'Swedish Hasbeens',
        'Sylvie Schimmel', 'Sylvio Giardina', 'T By Alexander Wang', 'T3', 'Tabitha Simmons', 'Tag Heuer', 'Tagliatore',
        'Taher Chemirik', 'Talika', 'Talitha', 'Tamara Mellon', 'Tanya Taylor', 'Tara4779', 'Tata Naka',
        'Temperley London', 'Ter Et Bantine', 'The Cambridge Satchel Company', 'The Elder Statesman', 'The Great',
        'The Last Conspiracy', 'The Row', 'The Seafarer', 'The Soloist', 'The Webster', 'Theo By Tim Van Steenbergen',
        'Theory', 'Thierry Lasry', 'Thierry Mugler Vintage', 'This Works', 'Thom Browne', 'Thom Krom', 'Thomas Wylde',
        'Thomasine', 'Thoraval', 'Thorsun', 'Tibi', 'Tobias Wistisen', 'Tod\'s', 'Toga', 'Tom Binns', 'Tomas Maier',
        'Tomasini', 'Tome', 'Tone It Up', 'Toogood', 'Tori Praver', 'Tory Burch', 'Trippen', 'Troubadour', 'True Rocks',
        'Tsumori Chisato', 'Tu Es Mon Tresor', 'Twin-Set', 'Ugo Cacciatori', 'Ulla Johnson', 'Ulla Soucasse',
        'Uma Wang', 'Unconditional', 'Undercover', 'Unif', 'United Nude', 'Unravel', 'Urban Zen', 'Uribe',
        'Uzerai Edits', 'V Jewellery', 'V73', 'Valas', 'Valdez Panama Hats', 'Valencia', 'Valentino',
        'Valentino Garavani', 'Valery Kovalska', 'Valextra', 'Vanessa Bruno', 'Vanessa Bruno Athé', 'Vans', 'Vava',
        'Veneffect', 'Venessa Arizaga', 'Venyx', 'Vera Wang', 'Veronica Beard', 'Veronique Branquinho',
        'Veronique Leroy', 'Versace', 'Versace Collection', 'Versace Vintage', 'Versus', 'Very Gavello', 'Vibe Harsløf',
        'Victoria Beckham', 'Victoria Beckham Denim', 'Victoria Victoria Beckham', 'Vieri', 'Vince', 'Vince Camuto',
        'Visvim', 'Vita Fede', 'Vita Kin', 'Vivetta', 'Vivienne Westwood', 'Vivienne Westwood Anglomania',
        'Vivienne Westwood Gold Label', 'Vivienne Westwood Red Label', 'Vojd Studios', 'W.I.T.H.',
        'Walter Van Beirendonck Vintage', 'Want Les Essentiels De La Vie', 'Warm-Me', 'Weber Hodel Feder',
        'Werkstatt:München', 'Westward Leaning', 'Windle And Moodie', 'Won Hundred', 'Wood Wood', 'Woolrich',
        'Wouters & Hendrix', 'Wouters & Hendrix Gold', 'Wrq.E.D', 'Wuki', 'Wunderkind', 'Wwake', 'Wxyz By Laura Wass',
        'Y / Project', 'Y-3', 'Y-3 By Yohji Yamamoto Vintage', 'Yang Li', 'Yannis Sergakis', 'Yazbukey', 'Yeezy',
        'Yigal Azrouel', 'YMC', 'Yohji Yamamoto', 'Yohji Yamamoto Vintage', 'Yosuzi', 'Yukié Deuxpoints',
        'Yves Saint Laurent Vintage', 'Yves Salomon', 'Yvonne Léon', 'Zac Zac Posen', 'Zadig & Voltaire', 'Zanellato',
        'Zanone', 'Zanzan', 'Zayiana', 'Zero + Maria Cornejo', 'Zilla', 'Zimmermann', 'Zoe And Morgan', 'Zoe Karssen',
        'Zuhair Murad',
    );

    /**
     * Value Type List
     *
     * @return array
     */
    public static function getValueTypeList()
    {
        return array(
            'int'      => 'int',
            'string'   => 'string',
            'color'    => 'color',
            'size'     => 'size',
            'designer' => 'designer',
        );
    }

    /**
     * Create random value
     *
     * @param string $type
     *
     * @return int|string
     */
    public function createValue($type, $locale)
    {
        if (!isset($this->faker)) {
            $this->faker = \Faker\Factory::create($locale);
        }

        switch ($type) {
            case 'int':
                $value = $this->faker->randomNumber();
                break;
            case 'string':
                $value = $this->faker->sentence(3);
                break;
            case 'color':
                $value = $this->faker->colorName;
                break;
            case 'size':
                $value = $this->sizes[array_rand($this->sizes)];
                break;
            case 'designer':
                $value = $this->designer[array_rand($this->designer)];
                break;
            default:
                $value = $this->faker->randomNumber();
        }

        return $value;
    }
}
