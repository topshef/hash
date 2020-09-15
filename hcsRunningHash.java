
//https://discord.com/channels/373889138199494658/673969691907325983/755360194753724526

/*
we first create a ByteArrayOutputStream to which we write:
-Current running hash
-RUNNING_HASH_VERSION
-Payer shard num
-Payer realm  num
-Payer account num
-TopicId shard num
-TopicId realm  num
-TopicId account num
-ConsensusTimestamp seconds
-ConsensusTimestamp nanos
-Sequence  number
-SHA384 Hash of the message


We hash all that and it becomes the running Hash

This is the actual code
This is in a class called MerkleTopic.java


*/

var boas = new ByteArrayOutputStream();
        try (var out = new ObjectOutputStream(boas)) {
            out.writeObject(getRunningHash());
            out.writeLong(RUNNING_HASH_VERSION);
            out.writeLong(payer.getShardNum());
            out.writeLong(payer.getRealmNum());
            out.writeLong(payer.getAccountNum());
            out.writeLong(topicId.getShardNum());
            out.writeLong(topicId.getRealmNum());
            out.writeLong(topicId.getTopicNum());
            out.writeLong(consensusTimestamp.getEpochSecond());
            out.writeInt(consensusTimestamp.getNano());
            ++sequenceNumber;
            out.writeLong(sequenceNumber);
            out.writeObject(MessageDigest.getInstance("SHA-384").digest(message));
            out.flush();
            runningHash = MessageDigest.getInstance("SHA-384").digest(boas.toByteArray());
        }